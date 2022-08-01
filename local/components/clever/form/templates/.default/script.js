$(document).ready(function() {


// popup windows

    var winPopup = document.querySelector('.wrap');
    var popup = document.querySelector('.js-popup');
    var close = document.querySelector('.js-close-popup');

    function showPopup(event) {
        popup.classList.add('modal-content-show');
        winPopup.classList.add('wrap--active');
    }

    function removePopup() {
        winPopup.classList.remove('wrap--active');
        popup.classList.remove('modal-content-show');
    };

    function windowOnClick(event) {
        if (event.target === winPopup || event.code === 'Escape') {
            removePopup();
        }
    }

    window.addEventListener('click', windowOnClick);
    window.addEventListener('keydown', windowOnClick);

    $('.js-request').on('click', function (event) {
        event.preventDefault();
        showPopup();
    });

    $('.js-close-popup').on('click', function (event) {
        event.preventDefault();
        removePopup();
    });

    //E-mail Ajax Send
    $('.js-getResponse').click(function() { //Change
        var form = $('#js_form').serialize();
        $.ajax({
            type: 'POST',
            url: 'http://rstudio.ru.com/wp-content/themes/pro-tour_by/mail/send.php', //Change
            data: form
        }).done(function() {
            showThank();
            setTimeout(function() {
                // Done Functions
                $('.cloud-form').trigger('reset');
            }, 1000);
        });
        return false;
    });
});
