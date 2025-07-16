import flatpickr from "flatpickr";
import { French} from "flatpickr/dist/l10n/fr.js";

const flatpickrs = document.querySelectorAll(".flatpickr");
flatpickrs.forEach((element) => {
    flatpickr(element, {
        time_24hr: true,
        enableTime: element.type === 'text',
        enableSeconds: element.type === 'text',
        minuteIncrement: 1,
        locale: "fr"
    })
});
