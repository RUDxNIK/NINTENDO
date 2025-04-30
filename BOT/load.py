import logging
import sqlite3
import random
import asyncio
from datetime import datetime, timedelta
import pytz
import configparser
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import Application, CommandHandler, CallbackQueryHandler, MessageHandler, filters, ContextTypes

logging.basicConfig(format='%(asctime)s - %(name)s - %(levelname)s - %(message)s', level=logging.INFO)
logger = logging.getLogger(__name__)

def init_db():
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    cursor.execute('CREATE TABLE IF NOT EXISTS computers (id INTEGER PRIMARY KEY, status BOOLEAN NOT NULL)')
    cursor.execute('CREATE TABLE IF NOT EXISTS bookings (booking_id TEXT PRIMARY KEY, user_id INTEGER NOT NULL, computer_id INTEGER NOT NULL, booking_time INTEGER NOT NULL, booking_date TEXT NOT NULL, phone_number TEXT NOT NULL)')
    cursor.execute('SELECT COUNT(*) FROM computers')
    count = cursor.fetchone()[0]
    if count == 0:
        for i in range(1, 21):
            cursor.execute('INSERT INTO computers (id, status) VALUES (?, ?)', (i, 0))
        conn.commit()
    conn.close()

def migrate_db():
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    try:
        cursor.execute('ALTER TABLE bookings ADD COLUMN booking_date TEXT')
        cursor.execute('UPDATE bookings SET booking_date = ?', (datetime.now(pytz.timezone('Europe/Moscow')).date().strftime('%Y-%m-%d'),))
        conn.commit()
    except sqlite3.OperationalError:
        pass
    conn.close()

def is_computer_booked(comp_id, hour):
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date()
    if now.hour >= 19:
        booking_date = (now + timedelta(days=1)).date()
    cursor.execute('SELECT COUNT(*) FROM bookings WHERE computer_id = ? AND booking_time = ? AND booking_date = ?',
                   (comp_id, hour, booking_date.strftime('%Y-%m-%d')))
    count = cursor.fetchone()[0]
    conn.close()
    return count > 0

def cleanup_expired_bookings():
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    cursor.execute('SELECT booking_id, booking_time, booking_date FROM bookings')
    bookings = cursor.fetchall()
    for booking_id, booking_time, booking_date in bookings:
        try:
            booking_datetime = datetime.strptime(f"{booking_date} {booking_time}:00", '%Y-%m-%d %H:%M')
            booking_datetime = pytz.timezone('Europe/Moscow').localize(booking_datetime)
            if now >= booking_datetime + timedelta(hours=1):
                cursor.execute('DELETE FROM bookings WHERE booking_id = ?', (booking_id,))
        except ValueError:
            logger.warning(f"Invalid booking date format for booking_id {booking_id}")
    conn.commit()
    conn.close()

def get_user_booking_count(user_id):
    cleanup_expired_bookings()
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    cursor.execute('SELECT COUNT(*) FROM bookings WHERE user_id = ?', (user_id,))
    count = cursor.fetchone()[0]
    conn.close()
    return count

def generate_booking_code():
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    while True:
        code = str(random.randint(1000, 9999))
        cursor.execute('SELECT COUNT(*) FROM bookings WHERE booking_id = ?', (code,))
        if cursor.fetchone()[0] == 0:
            conn.close()
            return code
    conn.close()

def book_computer(user_id, comp_id, hour, phone_number):
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    booking_id = generate_booking_code()
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date()
    if now.hour >= 19:
        booking_date = (now + timedelta(days=1)).date()
    cursor.execute('INSERT INTO bookings (booking_id, user_id, computer_id, booking_time, booking_date, phone_number) VALUES (?, ?, ?, ?, ?, ?)',
                   (booking_id, user_id, comp_id, hour, booking_date.strftime('%Y-%m-%d'), phone_number))
    conn.commit()
    conn.close()
    return booking_id

def get_user_bookings(user_id):
    cleanup_expired_bookings()
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    cursor.execute('SELECT computer_id, booking_time, phone_number, booking_id, booking_date FROM bookings WHERE user_id = ? ORDER BY booking_date, booking_time', (user_id,))
    bookings = cursor.fetchall()
    conn.close()
    return bookings

def get_upcoming_bookings():
    cleanup_expired_bookings()
    conn = sqlite3.connect('computers.db')
    cursor = conn.cursor()
    cursor.execute('SELECT booking_id, user_id, computer_id, booking_time, phone_number, booking_date FROM bookings')
    bookings = cursor.fetchall()
    conn.close()
    return bookings

def get_formatted_date(booking_date):
    months = ["января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"]
    date_obj = datetime.strptime(booking_date, '%Y-%m-%d')
    day = date_obj.day
    month = months[date_obj.month - 1]
    year = date_obj.year
    return f"{day} {month} {year}"

def is_tomorrow_booking():
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    return now.hour >= 19

WELCOME_MESSAGE = (
    "🎮 Добро пожаловать в компьютерный клуб *N1NTENDO*! 🎮\n\n"
    "Это место, где вы можете отвлечься от повседневной рутины, насладиться играми и общением.\n\n"
    "Нажмите кнопку ниже, чтобы забронировать компьютер."
)

async def send_reminders(context: ContextTypes.DEFAULT_TYPE):
    while True:
        now = datetime.now(pytz.timezone('Europe/Moscow'))
        current_hour = now.hour
        current_minute = now.minute
        bookings = get_upcoming_bookings()
        for booking_id, user_id, computer_id, booking_time, phone_number, booking_date in bookings:
            booking_datetime = datetime.strptime(f"{booking_date} {booking_time}:00", '%Y-%m-%d %H:%M')
            booking_datetime = pytz.timezone('Europe/Moscow').localize(booking_datetime)
            if booking_datetime.date() == now.date() and booking_time == current_hour and 45 <= current_minute <= 50:
                formatted_date = get_formatted_date(booking_date)
                await context.bot.send_message(
                    chat_id=user_id,
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
        await asyncio.sleep(60)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    keyboard = [[InlineKeyboardButton("Забронировать", callback_data="book")]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text(
        WELCOME_MESSAGE,
        reply_markup=reply_markup,
        parse_mode='Markdown'
    )

async def book(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    image_file = config['DEFAULT']['ImageFile']
    booking_count = get_user_booking_count(user_id)
    if booking_count >= 2:
        bookings = get_user_bookings(user_id)
        response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
            date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        await update.message.reply_text(
            response,
            parse_mode='Markdown'
        )
        return
    if booking_count == 1:
        bookings = get_user_bookings(user_id)
        response = "📋 Ваше текущее бронирование:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
            date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        response += "\nВыберите следующий компьютер:"
        await update.message.reply_text(
            response,
            parse_mode='Markdown'
        )
    keyboard = []
    for row in range(4):
        row_buttons = []
        for col in range(5):
            comp_num = row * 5 + col + 1
            row_buttons.append(InlineKeyboardButton(f"PC{comp_num}", callback_data=f"comp_{comp_num}"))
        keyboard.append(row_buttons)
    reply_markup = InlineKeyboardMarkup(keyboard)
    caption = "Выбери компьютер:" if not is_tomorrow_booking() else "Выбери компьютер (бронирование на завтра):"
    with open(image_file, 'rb') as photo:
        await update.message.reply_photo(
            photo=photo,
            caption=caption,
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )

async def bookings(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    bookings = get_user_bookings(user_id)
    if not bookings:
        keyboard = [[InlineKeyboardButton("Забронировать", callback_data="book")]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            "У вас нет активных бронирований.",
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )
        return
    response = "📋 Ваши бронирования:\n\n"
    for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
        date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
        response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
    await update.message.reply_text(
        response,
        parse_mode='Markdown'
    )

async def button(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    image_file = config['DEFAULT']['ImageFile']
    user_id = query.from_user.id
    if query.data == "book":
        booking_count = get_user_booking_count(user_id)
        if booking_count >= 2:
            bookings = get_user_bookings(user_id)
            response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
                date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            await query.message.reply_text(
                response,
                parse_mode='Markdown'
            )
            return
        if booking_count == 1:
            bookings = get_user_bookings(user_id)
            response = "📋 Ваше текущее бронирование:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
                date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            response += "\nВыберите следующий компьютер:"
            await query.message.reply_text(
                response,
                parse_mode='Markdown'
            )
        keyboard = []
        for row in range(4):
            row_buttons = []
            for col in range(5):
                comp_num = row * 5 + col + 1
                row_buttons.append(InlineKeyboardButton(f"PC{comp_num}", callback_data=f"comp_{comp_num}"))
            keyboard.append(row_buttons)
        reply_markup = InlineKeyboardMarkup(keyboard)
        caption = "Выбери компьютер:" if not is_tomorrow_booking() else "Выбери компьютер (бронирование на завтра):"
        with open(image_file, 'rb') as photo:
            await query.message.reply_photo(
                photo=photo,
                caption=caption,
                reply_markup=reply_markup,
                parse_mode='Markdown'
            )
    elif query.data.startswith("comp_"):
        comp_num = int(query.data.split("_")[1])
        if get_user_booking_count(user_id) >= 2:
            bookings = get_user_bookings(user_id)
            response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
            for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
                date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
                response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
            await query.message.reply_text(
                response,
                parse_mode='Markdown'
            )
            return
        now = datetime.now(pytz.timezone('Europe/Moscow'))
        current_hour = now.hour
        keyboard = []
        booked_hours = []
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
        if booked_hours:
            booked_row = []
            for hour in booked_hours:
                booked_row.append(InlineKeyboardButton(f"~~{hour}:00~~", callback_data="unavailable"))
            keyboard.append(booked_row)
        if not any(row for row in keyboard if any(btn.callback_data != "unavailable" for btn in row)):
            await query.message.reply_text(
                f"На PC{comp_num} нет свободных часов. 😔",
                parse_mode='Markdown'
            )
            return
        reply_markup = InlineKeyboardMarkup(keyboard)
        await query.message.reply_text(
            f"Выберите время для PC{comp_num}:",
            reply_markup=reply_markup,
            parse_mode='Markdown'
        )
    elif query.data == "unavailable":
        await query.message.reply_text(
            f"Это время уже забронировано. 😔",
            parse_mode='Markdown'
        )
    elif query.data.startswith("time_"):
        _, comp_num, hour = query.data.split("_")
        comp_num = int(comp_num)
        hour = int(hour)
        if is_computer_booked(comp_num, hour):
            await query.message.reply_text(
                f"PC{comp_num} на {hour}:00 уже забронирован. 😔",
                parse_mode='Markdown'
            )
            return
        context.user_data['comp_num'] = comp_num
        context.user_data['hour'] = hour
        await query.message.reply_text(
            "Введите ваш номер телефона (например, +79881373428):",
            parse_mode='Markdown'
        )

async def handle_phone_number(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if 'comp_num' not in context.user_data or 'hour' not in context.user_data:
        await update.message.reply_text(
            "Ошибка: начните процесс бронирования заново с /start.",
            parse_mode='Markdown'
        )
        return
    phone_number = update.message.text.strip()
    comp_num = context.user_data['comp_num']
    hour = context.user_data['hour']
    user_id = update.message.from_user.id
    if not phone_number.startswith("+") or len(phone_number) < 10:
        await update.message.reply_text(
            "Пожалуйста, введите корректный номер телефона (например, +79881373428):",
            parse_mode='Markdown'
        )
        return
    if get_user_booking_count(user_id) >= 2:
        bookings = get_user_bookings(user_id)
        response = "У вас уже есть 2 бронирования. 😔\n\n📋 Ваши бронирования:\n\n"
        for comp_id, booking_time, phone_number, booking_id, booking_date in bookings:
            date_str = datetime.strptime(booking_date, '%Y-%m-%d').strftime('%d.%m.%Y')
            response += f"PC{comp_id} | {date_str} с {booking_time}:00 до {booking_time + 1}:00 | {phone_number} | Код: {booking_id}\n"
        await update.message.reply_text(
            response,
            parse_mode='Markdown'
        )
        return
    booking_id = book_computer(user_id, comp_num, hour, phone_number)
    now = datetime.now(pytz.timezone('Europe/Moscow'))
    booking_date = now.date()
    if now.hour >= 19:
        booking_date = (now + timedelta(days=1)).date()
    formatted_date = get_formatted_date(booking_date.strftime('%Y-%m-%d'))
    keyboard = [[InlineKeyboardButton("Забронировать еще один компьютер", callback_data="book")]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text(
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
    context.user_data.clear()

async def error_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    logger.error(f"Update {update} caused error {context.error}")

def main():
    init_db()
    migrate_db()
    config = configparser.ConfigParser()
    config.read('bot.cfg')
    bot_token = config['DEFAULT']['BotToken']
    application = Application.builder().token(bot_token).build()
    application.job_queue.run_once(send_reminders, when=0)
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("book", book))
    application.add_handler(CommandHandler("bookings", bookings))
    application.add_handler(CallbackQueryHandler(button))
    application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_phone_number))
    application.add_error_handler(error_handler)
    application.run_polling()

if __name__ == '__main__':
    main()