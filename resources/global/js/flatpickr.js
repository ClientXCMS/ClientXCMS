import flatpickr from "flatpickr";
import { French} from "flatpickr/dist/l10n/fr.js";

const flatpickrs = document.querySelectorAll(".flatpickr");
flatpickrs.forEach((element) => {
    // Opt-in to seconds only when explicitly requested via data-enable-seconds="true".
    // Mobile native pickers cannot select sub-minute precision, so we default to minute
    // granularity for the textual flatpickr inputs as well.
    const wantsSeconds = element.dataset.enableSeconds === 'true';
    flatpickr(element, {
        time_24hr: true,
        enableTime: element.type === 'text',
        enableSeconds: wantsSeconds,
        minuteIncrement: 1,
        locale: "fr"
    })
});
