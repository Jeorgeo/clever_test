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

    function checkBarCode(status, code) {
        if (status) {
            $('.js-track').html(code);
            $('.js-form input[name=CODE]').val(code);
            $('.js-getResponse').prop('disabled', false);
            $('.js-input').removeClass('hidden');
        } else {
            $('.js-response').html('трек не найден');
            setTimeout(function() {
                $('.js-response').html('');
                removePopup;
            }, 1000);
        }
    }

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

    $('.js-getResponse').on('click', function (event) {
        event.preventDefault();
        let track = {
            ans: {
                code: $('.js-form input[name="CODE"]').val(),
                text: $('.js-form input[name="TEXT"]').val()
            }
        };
        let requestForText = JSON.stringify(track);
        $.ajax({
            method: 'POST',
            url: '/ajax/getAns.php',
            data: requestForText,
            dataType: 'json',
            success: function (result) {
                $('.js-response').html(result.response);
            }
        });
    });

    const params = {
        params: {
            id: $('.js-request input[name="ID"]').val() * 1 ,
            price: $('.js-request input[name="PRICE"]').val() * 1
        }
    };

    let requestForCode = JSON.stringify(params);
    $.ajax({
        method: 'POST',
        url: '/ajax/sendPrice.php',
        data: requestForCode,
        dataType: 'json',
        success: function (result) {
            checkBarCode(result.status, result.BarCode);
        }
    });
});
