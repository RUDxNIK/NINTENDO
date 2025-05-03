import logging
import mysql.connector
import mysql.connector.pooling
import random
import asyncio
from datetime import datetime, timedelta, date
import pytz
import configparser
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import Application, CommandHandler, CallbackQueryHandler, MessageHandler, filters, ContextTypes

logging.basicConfig(format='%(asctime)s - %(name)s - %(levelname)s - %(message)s', level=logging.INFO)
logger = logging.getLogger(__name__)

db_config = {
    'host': '37.140.192.16',
    'user': 'u3076068_lorenzo',
    'password': 'lorenzo12',
    'database': 'u3076068_nintendo_eshkere',
    'port': 3306,
    'pool_name': 'nintendo_pool',
    'pool_size': 5
}

db_pool = mysql.connector.pooling.MySQLConnectionPool(**db_config)

def get_db_connection():
    try:
        conn = db_pool.get_connection()
        if conn is None:
            logger.error("Failed to get database connection: pool returned None")
            return None
        return conn
    except mysql.connector.Error as e:
        logger.error(f"Failed to get database connection: {e}")
        return None

def is_telegram_linked(telegram_username):
    if not telegram_username:
        logger.warning("No telegram_username provided for is_telegram_linked")
        return False
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                logger.error("No database connection available for is_telegram_linked")
                return False
            cursor.execute(
                "SELECT is_linked FROM telegram_links WHERE telegram_username = %s AND is_linked = %s",
                (telegram_username, True)
            )
            result = cursor.fetchone()
            logger.info(f"is_telegram_linked: queried username={telegram_username}, result={result}")
            return result is not None and result[0]
    except mysql.connector.Error as e:
        logger.error(f"Database error in is_telegram_linked: {e}")
        return False

def link_telegram_account(telegram_id, telegram_username, code):
    if not telegram_username:
        return False, "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username)"
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                logger.error("No database connection available for link_telegram_account")
                return False, "Не удалось подключиться к базе данных"
            cursor.execute(
                "SELECT id, user_id FROM telegram_links WHERE telegram_code = %s AND is_linked = %s",
                (code, False)
            )
            result = cursor.fetchone()
            if not result:
                logger.warning(f"Invalid or already used code: {code}")
                return False, "Неверный код или аккаунт уже привязан"
            link_id, linked_user_id = result
            cursor.execute(
                "UPDATE telegram_links SET telegram_username = %s, telegram_id = %s, is_linked = %s, updated_at = %s WHERE id = %s",
                (telegram_username, telegram_id, True, datetime.now(pytz.timezone('Europe/Moscow')), link_id)
            )
            conn.commit()
            logger.info(f"Linked Telegram account: telegram_id={telegram_id}, telegram_username={telegram_username}, linked_user_id={linked_user_id}")
            return True, "✅ Аккаунт успешно привязан!"
    except mysql.connector.Error as e:
        logger.error(f"Database error in link_telegram_account: {e}")
        return False, f"Ошибка базы данных: {e}"

def is_computer_booked(comp_id, hour):
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date() if now.hour < 19 else (now + timedelta(days=1)).date()
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                return False
            cursor.execute(
                "SELECT COUNT(*) FROM bookings WHERE computer_id = %s AND booking_time = %s AND booking_date = %s",
                (comp_id, hour, booking_date)
            )
            return cursor.fetchone()[0] > 0
    except mysql.connector.Error as e:
        logger.error(f"Database error in is_computer_booked: {e}")
        return False

def cleanup_expired_bookings():
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                return
            cursor.execute("SELECT booking_id, booking_time, booking_date FROM bookings")
            bookings = cursor.fetchall()
            for booking_id, booking_time, booking_date in bookings:
                try:
                    booking_datetime = datetime.combine(booking_date, datetime.min.time()) + timedelta(hours=booking_time)
                    booking_datetime = pytz.timezone('Europe/Moscow').localize(booking_datetime)
                    if now >= booking_datetime + timedelta(hours=1):
                        cursor.execute("DELETE FROM bookings WHERE booking_id = %s", (booking_id,))
                except ValueError:
                    logger.warning(f"Invalid booking date for booking_id {booking_id}")
            conn.commit()
    except mysql.connector.Error as e:
        logger.error(f"Database error in cleanup_expired_bookings: {e}")

def get_user_booking_count(user_id):
    cleanup_expired_bookings()
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                return 0
            cursor.execute("SELECT COUNT(*) FROM bookings WHERE user_id = %s", (user_id,))
            return cursor.fetchone()[0]
    except mysql.connector.Error as e:
        logger.error(f"Database error in get_user_booking_count: {e}")
        return 0

def generate_booking_code():
    tries = 0
    max_tries = 5
    while tries < max_tries:
        try:
            with get_db_connection() as conn, conn.cursor() as cursor:
                if conn is None:
                    return None
                code = str(random.randint(1000, 9999))
                cursor.execute("SELECT COUNT(*) FROM bookings WHERE booking_id = %s", (code,))
                if cursor.fetchone()[0] == 0:
                    return code
                tries += 1
                logger.warning(f"Duplicate booking_id {code}, retry {tries}/{max_tries}")
        except mysql.connector.Error as e:
            logger.error(f"Database error in generate_booking_code: {e}")
            return None
    logger.error(f"Failed to generate unique booking_id after {max_tries} tries")
    return None

def book_computer(user_id, comp_id, hour, phone_number, telegram_username, telegram_id):
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date() if now.hour < 19 else (now + timedelta(days=1)).date()
    booking_id = generate_booking_code()
    if not booking_id:
        logger.error("Failed to generate booking_id")
        return None
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                logger.error("No database connection available")
                return None
            cursor.execute(
                "INSERT INTO bookings (booking_id, user_id, computer_id, booking_time, booking_date, phone_number, username, telegram_id) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
                (booking_id, user_id, comp_id, hour, booking_date, phone_number, telegram_username, telegram_id)
            )
            conn.commit()
            logger.info(f"Booked computer: user_id={user_id}, telegram_id={telegram_id}, computer_id={comp_id}, time={hour}, booking_id={booking_id}, username={telegram_username}")
            return booking_id
    except mysql.connector.Error as e:
        logger.error(f"Database error in book_computer: {e} (errno: {e.errno})")
        return None

def get_user_bookings(user_id):
    cleanup_expired_bookings()
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                return []
            cursor.execute(
                "SELECT computer_id, booking_time, phone_number, booking_id, booking_date, telegram_id FROM bookings "
                "WHERE user_id = %s ORDER BY booking_date, booking_time",
                (user_id,)
            )
            return cursor.fetchall()
    except mysql.connector.Error as e:
        logger.error(f"Database error in get_user_bookings: {e}")
        return []

def get_upcoming_bookings():
    cleanup_expired_bookings()
    try:
        with get_db_connection() as conn, conn.cursor() as cursor:
            if conn is None:
                return []
            cursor.execute("SELECT booking_id, user_id, computer_id, booking_time, phone_number, booking_date, telegram_id FROM bookings")
            return cursor.fetchall()
    except mysql.connector.Error as e:
        logger.error(f"Database error in get_upcoming_bookings: {e}")
        return []

def get_formatted_date(booking_date):
    months = ["января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"]
    if isinstance(booking_date, date):
        date_obj = booking_date
    else:
        date_obj = datetime.strptime(booking_date, '%Y-%m-%d')
    return f"{date_obj.day} {months[date_obj.month - 1]} {date_obj.year}"

def is_tomorrow_booking():
    return datetime.now(pytz.timezone('Europe/Moscow')).hour >= 19

WELCOME_MESSAGE = (
    "🎮 Добро пожаловать в компьютерный клуб *N1NTENDO*! 🎮\n\n"
    "Это место, где вы можете отвлечься от повседневной рутины, насладиться играми и общением.\n\n"
    "Нажмите кнопку ниже, чтобы забронировать компьютер."
)

UNLINKED_MESSAGE = (
    "🎮 Привет, геймер! 🎮\n\n"
    "Чтобы бронировать компьютеры в *N1NTENDO*, нужно привязать твой Telegram-аккаунт к сайту.\n\n"
    "1. Зарегистрируйся на сайте: [N1NTENDO](http://loaderaw.ru/%D0%9A%D0%BE%D0%BC%D0%B0%D0%BD%D0%B4%D0%B0%201/dashboard.php)\n"
    "2. Войди в личный кабинет и получи код для привязки\n"
    "3. Отправь код сюда с помощью команды: `/link <твой_код>`\n\n"
    "После привязки ты сможешь бронировать компьютеры прямо здесь! 🚀"
)

async def send_reminders(context: ContextTypes.DEFAULT_TYPE):
    while True:
        now = datetime.now(pytz.timezone('Europe/Moscow'))
        current_hour, current_minute = now.hour, now.minute
        try:
            bookings = get_upcoming_bookings()
            for booking_id, user_id, computer_id, booking_time, phone_number, booking_date, telegram_id in bookings:
                booking_datetime = datetime.combine(booking_date, datetime.min.time()) + timedelta(hours=booking_time)
                booking_datetime = pytz.timezone('Europe/Moscow').localize(booking_datetime)
                if booking_datetime.date() == now.date() and booking_time == current_hour and 45 <= current_minute <= 50:
                    formatted_date = get_formatted_date(booking_date)
                    start_time = datetime.now()
                    await context.bot.send_message(
                        chat_id=telegram_id,
                        text=(
                            f"⏰ Напоминание о бронировании!\n\n"
                            f"Дата: {formatted_date}\n"
                            f"Компьютер: PC{computer_id}\n"
                            f"Время: с {booking_time}:00 до {booking_time + 1}:00\n"
                            f"Ваш номер: {phone_number}\n"
                            f"Код бронирования: *{booking_id}*"
                        ),
                        parse_mode='Markdown'
                    )
                    logger.info(f"Reminder sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
                    await asyncio.sleep(0.2)
        except Exception as e:
            logger.error(f"Error in send_reminders: {e}")
        await asyncio.sleep(60)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    telegram_username = f"@{update.message.from_user.username}" if update.message.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await update.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={user_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await update.message.reply_text(
            UNLINKED_MESSAGE,
            parse_mode='Markdown',
            disable_web_page_preview=True
        )
        logger.info(f"Unlinked account message sent to telegram_id={user_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    keyboard = [[InlineKeyboardButton("Забронировать", callback_data="book")]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    start_time = datetime.now()
    await update.message.reply_text(
        WELCOME_MESSAGE,
        reply_markup=reply_markup,
        parse_mode='Markdown'
    )
    logger.info(f"Start message sent to telegram_id={user_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    await asyncio.sleep(0.2)

async def link(update: Update, context: ContextTypes.DEFAULT_TYPE):
    telegram_id = update.message.from_user.id
    telegram_username = f"@{update.message.from_user.username}" if update.message.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await update.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await update.message.reply_text(
            "✅ Ваш Telegram-аккаунт уже привязан к сайту! Вы можете бронировать компьютеры с помощью /start.",
            parse_mode='Markdown'
        )
        logger.info(f"Already linked message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not context.args:
        start_time = datetime.now()
        await update.message.reply_text(
            "ℹ️ Пожалуйста, укажите код для привязки. Например: `/link 1234567890abcdef`",
            parse_mode='Markdown'
        )
        logger.info(f"No code provided message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    code = context.args[0].strip()
    success, message = link_telegram_account(telegram_id, telegram_username, code)
    start_time = datetime.now()
    await update.message.reply_text(
        message,
        parse_mode='Markdown'
    )
    logger.info(f"Link result ({'success' if success else 'failure'}) sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    await asyncio.sleep(0.2)
    if success:
        keyboard = [[InlineKeyboardButton("Забронировать", callback_data="book")]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            WELCOME_MESSAGE,
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )
        logger.info(f"Welcome message sent to telegram_id={telegram_id} after linking in {(datetime.now() - start_time).total_seconds()} seconds")

async def book(update: Update, context: ContextTypes.DEFAULT_TYPE):
    telegram_id = update.message.from_user.id
    telegram_username = f"@{update.message.from_user.username}" if update.message.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await update.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await update.message.reply_text(
            UNLINKED_MESSAGE,
            parse_mode='Markdown',
            disable_web_page_preview=True
        )
        logger.info(f"Unlinked account message sent to telegram_id={telegram_id} with username={telegram_username} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    image_file = config['DEFAULT']['ImageFile']
    booking_count = get_user_booking_count(telegram_id)
    if booking_count >= 2:
        bookings = get_user_bookings(telegram_id)
        response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
            date_str = booking_date.strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        start_time = datetime.now()
        await update.message.reply_text(response, parse_mode='Markdown')
        logger.info(f"Booking limit message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if booking_count == 1:
        bookings = get_user_bookings(telegram_id)
        response = "📋 Ваше текущее бронирование:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
            date_str = booking_date.strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        response += "\nВыберите следующий компьютер:"
        start_time = datetime.now()
        await update.message.reply_text(response, parse_mode='Markdown')
        logger.info(f"Current booking message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
    keyboard = [[InlineKeyboardButton(f"PC{row * 5 + col + 1}", callback_data=f"comp_{row * 5 + col + 1}")
                for col in range(5)] for row in range(4)]
    reply_markup = InlineKeyboardMarkup(keyboard)
    caption = "Выбери компьютер:" if not is_tomorrow_booking() else "Выбери компьютер (бронирование на завтра):"
    try:
        with open(image_file, 'rb') as photo:
            start_time = datetime.now()
            await update.message.reply_photo(
                photo=photo,
                caption=caption,
                reply_markup=reply_markup,
                parse_mode='Markdown'
            )
            logger.info(f"Computer selection photo sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    except FileNotFoundError:
        logger.error(f"Image file {image_file} not found")
        start_time = datetime.now()
        await update.message.reply_text(caption, reply_markup=reply_markup, parse_mode='Markdown')
        logger.info(f"Computer selection text sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    await asyncio.sleep(0.2)

async def bookings(update: Update, context: ContextTypes.DEFAULT_TYPE):
    telegram_id = update.message.from_user.id
    telegram_username = f"@{update.message.from_user.username}" if update.message.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await update.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await update.message.reply_text(
            UNLINKED_MESSAGE,
            parse_mode='Markdown',
            disable_web_page_preview=True
        )
        logger.info(f"Unlinked account message sent to telegram_id={telegram_id} with username={telegram_username} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    bookings = get_user_bookings(telegram_id)
    if not bookings:
        keyboard = [[InlineKeyboardButton("Забронировать", callback_data="book")]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        start_time = datetime.now()
        await update.message.reply_text(
            "У вас нет активных бронирований.",
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )
        logger.info(f"No bookings message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    response = "📋 Ваши бронирования:\n\n"
    for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
        date_str = booking_date.strftime('%d.%m.%Y')
        response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
    start_time = datetime.now()
    await update.message.reply_text(response, parse_mode='Markdown')
    logger.info(f"Bookings list sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    await asyncio.sleep(0.2)

async def button(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    image_file = config['DEFAULT']['ImageFile']
    telegram_id = query.from_user.id
    telegram_username = f"@{query.from_user.username}" if query.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await query.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await query.message.reply_text(
            UNLINKED_MESSAGE,
            parse_mode='Markdown',
            disable_web_page_preview=True
        )
        logger.info(f"Unlinked account message sent to telegram_id={telegram_id} with username={telegram_username} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if query.data == "book":
        booking_count = get_user_booking_count(telegram_id)
        if booking_count >= 2:
            bookings = get_user_bookings(telegram_id)
            response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
                date_str = booking_date.strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            start_time = datetime.now()
            await query.message.reply_text(response, parse_mode='Markdown')
            logger.info(f"Booking limit message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            await asyncio.sleep(0.2)
            return
        if booking_count == 1:
            bookings = get_user_bookings(telegram_id)
            response = "📋 Ваше текущее бронирование:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
                date_str = booking_date.strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            response += "\nВыберите следующий компьютер:"
            start_time = datetime.now()
            await query.message.reply_text(response, parse_mode='Markdown')
            logger.info(f"Current booking message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            await asyncio.sleep(0.2)
        keyboard = [[InlineKeyboardButton(f"PC{row * 5 + col + 1}", callback_data=f"comp_{row * 5 + col + 1}")
                    for col in range(5)] for row in range(4)]
        reply_markup = InlineKeyboardMarkup(keyboard)
        caption = "Выбери компьютер:" if not is_tomorrow_booking() else "Выбери компьютер (бронирование на завтра):"
        try:
            with open(image_file, 'rb') as photo:
                start_time = datetime.now()
                await query.message.reply_photo(
                    photo=photo,
                    caption=caption,
                    reply_markup=reply_markup,
                    parse_mode='Markdown'
                )
                logger.info(f"Computer selection photo sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        except FileNotFoundError:
            logger.error(f"Image file {image_file} not found")
            start_time = datetime.now()
            await query.message.reply_text(caption, reply_markup=reply_markup, parse_mode='Markdown')
            logger.info(f"Computer selection text sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
    elif query.data.startswith("comp_"):
        comp_num = int(query.data.split("_")[1])
        if get_user_booking_count(telegram_id) >= 2:
            bookings = get_user_bookings(telegram_id)
            response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
                date_str = booking_date.strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            start_time = datetime.now()
            await query.message.reply_text(response, parse_mode='Markdown')
            logger.info(f"Booking limit message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            await asyncio.sleep(0.2)
            return
        now = datetime.now(pytz.timezone('Europe/Moscow'))
        current_hour = now.hour
        keyboard = []
        booked_hours = []
        context.user_data['booked_hours_cache'] = context.user_data.get('booked_hours_cache', {})
        cache_key = f"{comp_num}_{now.date()}"
        if cache_key not in context.user_data['booked_hours_cache']:
            for i in range(8, 20, 4):
                row = []
                for hour in range(i, min(i + 4, 20)):
                    if is_computer_booked(comp_num, hour):
                        booked_hours.append(hour)
                    elif not is_tomorrow_booking() and hour < current_hour:
                        continue
                    else:
                        row.append(InlineKeyboardButton(f"{hour}:00", callback_data=f"time_{comp_num}_{hour}"))
                if row:
                    keyboard.append(row)
            context.user_data['booked_hours_cache'][cache_key] = booked_hours
        else:
            booked_hours = context.user_data['booked_hours_cache'][cache_key]
            for i in range(8, 20, 4):
                row = []
                for hour in range(i, min(i + 4, 20)):
                    if hour in booked_hours:
                        continue
                    elif not is_tomorrow_booking() and hour < current_hour:
                        continue
                    else:
                        row.append(InlineKeyboardButton(f"{hour}:00", callback_data=f"time_{comp_num}_{hour}"))
                if row:
                    keyboard.append(row)
        if booked_hours:
            keyboard.append([InlineKeyboardButton(f"~~{hour}:00~~", callback_data="unavailable") for hour in booked_hours])
        if not any(row for row in keyboard if any(btn.callback_data != "unavailable" for btn in row)):
            start_time = datetime.now()
            await query.message.reply_text(f"На PC{comp_num} нет свободных часов. 😔", parse_mode='Markdown')
            logger.info(f"No available hours message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            await asyncio.sleep(0.2)
            return
        reply_markup = InlineKeyboardMarkup(keyboard)
        start_time = datetime.now()
        await query.message.reply_text(f"Выберите время для PC{comp_num}:", reply_markup=reply_markup, parse_mode='Markdown')
        logger.info(f"Time selection message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
    elif query.data == "unavailable":
        start_time = datetime.now()
        await query.message.reply_text(f"Это время уже забронировано. 😔", parse_mode='Markdown')
        logger.info(f"Unavailable time message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
    elif query.data.startswith("time_"):
        _, comp_num, hour = query.data.split("_")
        comp_num, hour = int(comp_num), int(hour)
        if is_computer_booked(comp_num, hour):
            start_time = datetime.now()
            await query.message.reply_text(f"PC{comp_num} на {hour}:00 уже забронирован. 😔", parse_mode='Markdown')
            logger.info(f"Booked time message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            await asyncio.sleep(0.2)
            return
        context.user_data['comp_num'] = comp_num
        context.user_data['hour'] = hour
        context.user_data['timestamp'] = datetime.now()
        start_time = datetime.now()
        await query.message.reply_text("Введите ваш номер телефона (например, +79881373428):", parse_mode='Markdown')
        logger.info(f"Phone number request sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
    elif query.data.startswith("confirm_"):
        comp_num = context.user_data.get('comp_num')
        hour = context.user_data.get('hour')
        phone_number = context.user_data.get('phone_number')
        if not all([comp_num, hour, phone_number]):
            start_time = datetime.now()
            await query.message.reply_text("Ошибка: данные бронирования устарели. Начните заново с /start.", parse_mode='Markdown')
            logger.info(f"Expired booking data message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            context.user_data.clear()
            await asyncio.sleep(0.2)
            return
        if is_computer_booked(comp_num, hour):
            start_time = datetime.now()
            await query.message.reply_text(f"PC{comp_num} на {hour}:00 уже забронирован. 😔", parse_mode='Markdown')
            logger.info(f"Booked time message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            context.user_data.clear()
            await asyncio.sleep(0.2)
            return
        try:
            with get_db_connection() as conn, conn.cursor() as cursor:
                if conn is None:
                    logger.error("No database connection available")
                    return None
                cursor.execute(
                    "SELECT user_id FROM telegram_links WHERE telegram_id = %s AND is_linked = %s",
                    (telegram_id, True)
                )
                user_id = cursor.fetchone()
                if not user_id:
                    start_time = datetime.now()
                    await query.message.reply_text("Ошибка: ваш Telegram-аккаунт не привязан. Используйте /link.", parse_mode='Markdown')
                    logger.info(f"Unlinked account error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
                    context.user_data.clear()
                    await asyncio.sleep(0.2)
                    return
                user_id = user_id[0]
        except mysql.connector.Error as e:
            logger.error(f"Database error in fetching user_id: {e}")
            start_time = datetime.now()
            await query.message.reply_text("Ошибка базы данных. Попробуйте снова.", parse_mode='Markdown')
            logger.info(f"Database error message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            context.user_data.clear()
            await asyncio.sleep(0.2)
            return
        booking_id = book_computer(user_id, comp_num, hour, phone_number, telegram_username, telegram_id)
        if not booking_id:
            start_time = datetime.now()
            await query.message.reply_text("Не удалось создать бронирование. Попробуйте снова.", parse_mode='Markdown')
            logger.info(f"Booking failure message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
            context.user_data.clear()
            await asyncio.sleep(0.2)
            return
        now = datetime.now(pytz.timezone('Europe/Moscow'))
        booking_date = now.date() if now.hour < 19 else (now + timedelta(days=1)).date()
        formatted_date = get_formatted_date(booking_date)
        keyboard = [[InlineKeyboardButton("Забронировать еще один компьютер", callback_data="book")]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        start_time = datetime.now()
        await query.message.reply_text(
            f"Бронирование успешно!\n\n"
            f"Дата: {formatted_date}\n"
            f"Компьютер: PC{comp_num}\n"
            f"Время: с {hour}:00 до {hour + 1}:00\n"
            f"Ваш номер: {phone_number}\n"
            f"Код бронирования: *{booking_id}*\n\n"
            f"Сохраните этот код!",
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )
        logger.info(f"Booking confirmation sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        context.user_data.clear()
        await asyncio.sleep(0.2)
    elif query.data == "cancel":
        start_time = datetime.now()
        await query.message.reply_text("Бронирование отменено. Начните заново с /start.", parse_mode='Markdown')
        logger.info(f"Booking cancellation message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        context.user_data.clear()
        await asyncio.sleep(0.2)

async def handle_phone_number(update: Update, context: ContextTypes.DEFAULT_TYPE):
    telegram_id = update.message.from_user.id
    telegram_username = f"@{update.message.from_user.username}" if update.message.from_user.username else None
    if not telegram_username:
        start_time = datetime.now()
        await update.message.reply_text(
            "🚫 Ошибка: у вашего Telegram-аккаунта нет имени пользователя (@username). Установите имя пользователя в настройках Telegram и попробуйте снова.",
            parse_mode='Markdown'
        )
        logger.info(f"No username error sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if not is_telegram_linked(telegram_username):
        start_time = datetime.now()
        await update.message.reply_text(
            UNLINKED_MESSAGE,
            parse_mode='Markdown',
            disable_web_page_preview=True
        )
        logger.info(f"Unlinked account message sent to telegram_id={telegram_id} with username={telegram_username} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if 'comp_num' not in context.user_data or 'hour' not in context.user_data:
        start_time = datetime.now()
        await update.message.reply_text(
            "Ошибка: начните процесс бронирования заново с /start.",
            parse_mode='Markdown'
        )
        logger.info(f"Invalid booking data message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    timestamp = context.user_data.get('timestamp')
    if timestamp and (datetime.now() - timestamp).total_seconds() > 300:
        start_time = datetime.now()
        await update.message.reply_text(
            "Время ожидания истекло. Начните заново с /start.",
            parse_mode='Markdown'
        )
        logger.info(f"Timeout message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        context.user_data.clear()
        await asyncio.sleep(0.2)
        return
    phone_number = update.message.text.strip()
    comp_num = context.user_data['comp_num']
    hour = context.user_data['hour']
    if not phone_number.startswith("+") or len(phone_number) < 10:
        start_time = datetime.now()
        await update.message.reply_text(
            "Пожалуйста, введите корректный номер телефона (например, +79881373428):",
            parse_mode='Markdown'
        )
        logger.info(f"Invalid phone number message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        await asyncio.sleep(0.2)
        return
    if get_user_booking_count(telegram_id) >= 2:
        bookings = get_user_bookings(telegram_id)
        response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date, telegram_id in bookings:
            date_str = booking_date.strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        start_time = datetime.now()
        await update.message.reply_text(response, parse_mode='Markdown')
        logger.info(f"Booking limit message sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
        context.user_data.clear()
        await asyncio.sleep(0.2)
        return
    context.user_data['phone_number'] = phone_number
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date() if now.hour < 19 else (now + timedelta(days=1)).date()
    formatted_date = get_formatted_date(booking_date)
    keyboard = [
        [InlineKeyboardButton("Подтвердить", callback_data=f"confirm_{comp_num}_{hour}"),
         InlineKeyboardButton("Отменить", callback_data="cancel")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    start_time = datetime.now()
    await update.message.reply_text(
        f"Подтвердите бронирование:\n\n"
        f"Дата: {formatted_date}\n"
        f"Компьютер: PC{comp_num}\n"
        f"Время: с {hour}:00 до {hour + 1}:00\n"
        f"Ваш номер: {phone_number}\n\n"
        f"Нажмите 'Подтвердить' для завершения или 'Отменить' для отмены.",
        reply_markup=reply_markup,
        parse_mode='Markdown'
    )
    logger.info(f"Booking confirmation prompt sent to telegram_id={telegram_id} in {(datetime.now() - start_time).total_seconds()} seconds")
    await asyncio.sleep(0.2)

async def error_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    logger.error(f"Update {update} caused error {context.error}")

def main():
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    bot_token = config['DEFAULT']['BotToken']
    application = Application.builder().token(bot_token).build()
    application.job_queue.run_once(send_reminders, when=0)
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("book", book))
    application.add_handler(CommandHandler("bookings", bookings))
    application.add_handler(CommandHandler("link", link))
    application.add_handler(CallbackQueryHandler(button))
    application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_phone_number))
    application.add_error_handler(error_handler)
    application.run_polling()

if __name__ == '__main__':
    main()