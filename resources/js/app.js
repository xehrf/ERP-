document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-day-calculator]');

    if (!form) {
        return;
    }

    const startInput = form.querySelector('[name="start_date"]');
    const endInput = form.querySelector('[name="end_date"]');
    const calendarOutput = form.querySelector('[data-calendar-days]');
    const workingOutput = form.querySelector('[data-working-days]');

    const updateDays = () => {
        const start = startInput.value ? new Date(`${startInput.value}T00:00:00`) : null;
        const end = endInput.value ? new Date(`${endInput.value}T00:00:00`) : null;

        if (!start || !end || end < start) {
            calendarOutput.textContent = '0';
            workingOutput.textContent = '0';
            return;
        }

        let calendarDays = 0;
        let workingDays = 0;
        const current = new Date(start);

        while (current <= end) {
            calendarDays += 1;
            const day = current.getDay();

            if (day !== 0 && day !== 6) {
                workingDays += 1;
            }

            current.setDate(current.getDate() + 1);
        }

        calendarOutput.textContent = calendarDays;
        workingOutput.textContent = workingDays;
    };

    startInput.addEventListener('change', updateDays);
    endInput.addEventListener('change', updateDays);
    updateDays();
});
