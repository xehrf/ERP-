// Этот JavaScript отвечает за подсчет дней в форме создания заявки.
// Он работает только на странице, где есть форма с атрибутом data-day-calculator.

// Ждем, пока браузер полностью загрузит HTML-страницу.
document.addEventListener('DOMContentLoaded', () => {
    // Ищем форму создания заявки.
    const form = document.querySelector('[data-day-calculator]');

    // Если такой формы на странице нет, прекращаем выполнение кода.
    if (!form) {
        return;
    }

    // Поле "Дата начала".
    const startInput = form.querySelector('[name="start_date"]');
    // Поле "Дата конца".
    const endInput = form.querySelector('[name="end_date"]');
    // Элемент, куда выводятся календарные дни.
    const calendarOutput = form.querySelector('[data-calendar-days]');
    // Элемент, куда выводятся рабочие дни.
    const workingOutput = form.querySelector('[data-working-days]');
    // Блок предупреждения о пересечении дат.
    const overlapWarning = form.querySelector('[data-overlap-warning]');
    // Блок календаря хранит занятые периоды в JSON.
    const busyCalendar = form.querySelector('[data-busy-calendar]');
    // Превращаем JSON занятых периодов в массив JavaScript.
    const busyRequests = busyCalendar ? JSON.parse(busyCalendar.dataset.busyCalendar || '[]') : [];

    // Функция пересчитывает дни каждый раз, когда меняется дата.
    const updateDays = () => {
        // Превращаем дату начала из строки в объект Date.
        const start = startInput.value ? new Date(`${startInput.value}T00:00:00`) : null;
        // Превращаем дату конца из строки в объект Date.
        const end = endInput.value ? new Date(`${endInput.value}T00:00:00`) : null;

        // Если дата не выбрана или конец раньше начала, показываем нули.
        if (!start || !end || end < start) {
            calendarOutput.textContent = '0';
            workingOutput.textContent = '0';
            overlapWarning.hidden = true;
            return;
        }

        // Счетчик календарных дней.
        let calendarDays = 0;
        // Счетчик рабочих дней.
        let workingDays = 0;
        // current будет двигаться от даты начала к дате конца.
        const current = new Date(start);

        // Цикл идет по каждому дню выбранного периода.
        while (current <= end) {
            // Каждый день считается календарным.
            calendarDays += 1;
            // getDay() возвращает день недели: 0 - воскресенье, 6 - суббота.
            const day = current.getDay();

            // Если это не воскресенье и не суббота, день считается рабочим.
            if (day !== 0 && day !== 6) {
                workingDays += 1;
            }

            // Переходим к следующему дню.
            current.setDate(current.getDate() + 1);
        }

        // Показываем календарные дни на странице.
        calendarOutput.textContent = calendarDays;
        // Показываем рабочие дни на странице.
        workingOutput.textContent = workingDays;

        // Проверяем пересечение выбранного периода с уже занятыми периодами.
        const hasOverlap = busyRequests.some((busy) => {
            const busyStart = new Date(`${busy.start_date}T00:00:00`);
            const busyEnd = new Date(`${busy.end_date}T00:00:00`);

            return busyStart <= end && busyEnd >= start;
        });

        // Показываем или скрываем предупреждение.
        overlapWarning.hidden = !hasOverlap;
    };

    // Пересчитываем дни при изменении даты начала.
    startInput.addEventListener('change', updateDays);
    // Пересчитываем дни при изменении даты конца.
    endInput.addEventListener('change', updateDays);
    // Запускаем расчет сразу при загрузке страницы.
    updateDays();
});
