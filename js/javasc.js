
var obavijestiNAV = document.getElementById('obavijestiNAV');
var dropdownIsOpen = false;

obavijestiNAV.addEventListener('click', function () {
    // Toggle the class based on dropdown state
    if (dropdownIsOpen) {
        obavijestiNAV.querySelector('i').className = 'fa-sharp fa-solid fa-envelope';
    } else {
        obavijestiNAV.querySelector('i').className = 'fa-solid fa-envelope-open';
    }

    // Toggle the dropdown state
    dropdownIsOpen = !dropdownIsOpen;
});

