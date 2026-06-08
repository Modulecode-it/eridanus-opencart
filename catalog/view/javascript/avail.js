jQuery(document).ready(function(){

    //получаем настройки модуля
    getAvailConfig();


    //обработка опций и открытие мод. окна для товара
    $( document ).on( "click", ".notify_product", function(e) {
        $('.success, .warning, .attention, information, .error').remove();
        product_id = $('.aval-product-page-id').text();//получаем id товара
        var data_avail = $(blok_in_productpage + " input[type=\'text\'], " +blok_in_productpage + " input[type=\'hidden\'], " +blok_in_productpage + "  input[type=\'radio\']:checked, " +blok_in_productpage + "  input[type=\'checkbox\']:checked, " +blok_in_productpage + "  select, " +blok_in_productpage + "  textarea, " +blok_in_productpage + "  img").serialize();
        if(!data_avail.match(/product_id/)){
            data_avail = data_avail + "&product_id="+product_id;
        };

        $.ajax({
            url: 'index.php?route=extension/module/avail/ValidOption',
            type: 'post',
            data: data_avail,
            dataType: 'json',
            success: function(json) {
                $('.alert, .text-danger').remove();
                $('.form-group').removeClass('has-error');

                if (json['error']) {
                    if (json['error']['option']) {
                        for (i in json['error']['option']) {
                            var element = $('#input-option' + i.replace('_', '-'));

                            if (element.parent().hasClass('input-group')) {
                                element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                            } else {
                                element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                            }
                        }
                    }

                    if (json['error']['recurring']) {
                        $('select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
                    }

                    // Highlight any found errors
                    $('.text-danger').parent().addClass('has-error');
                }

                if (json['success']) {

                    $('.has-error').removeClass('has-error');
                    var data_avail = $(blok_in_productpage + " input[type=\'hidden\'], " + blok_in_productpage + " input[type=\'radio\']:checked, " + blok_in_productpage + " input[type=\'checkbox\']:checked, " + blok_in_productpage + " select, " + blok_in_productpage + " img, " + "#input-quantity");
                    $.ajax({
                        cache: false,
                        type: 'POST',
                        url: 'index.php?route=extension/module/avail/openForm',
                        data: data_avail,
                        success: function(data){
                            var newStart = data.substr(0,data.indexOf('start captcha')+16); //html string before captcha
                            var newEnd = data.substr(data.indexOf('end captcha')-4,data.length); //html string after captcha

                            //oldHTML.replace()

                            $('.avanoti .modal-content .before-captcha').html(newStart);
                            $('.avanoti .modal-content .after-captcha').html(newEnd);
                            //$('.avanoti .modal-content').html(data);
                            $('#edit-modal-avail').modal('show');
                        }
                    });
                    $('body').removeClass('modal-open');
                    $("body").css({ 'padding-right' : ''});
                }
            }
        });
    });

    //открытие модального окна для миниатюр
    $( document ).on( "click", ".notify", function(e) {
        $("#edit-modal-avail").modal();

        product_id_avail = $(this).data('product_id_avail');
        language_id = $(this).data('language_id');
        $.ajax({
            cache: false,
            type: 'POST',
            url: 'index.php?route=extension/module/avail/openForm',
            data: {product_id:product_id_avail, language_id:language_id},
            success: function(data){
                var newStart = data.substr(0,data.indexOf('start captcha')+16); //html string before captcha
                var newEnd = data.substr(data.indexOf('end captcha')-4,data.length); //html string after captcha
                $('.avanoti .modal-content .before-captcha').html(newStart);
                $('.avanoti .modal-content .after-captcha').html(newEnd);
                //$('.avanoti .modal-content').html(data);
            }
        });
        $('body').removeClass('modal-open');
        $("body").css({ 'padding-right' : ''});
    });
    $('#edit-modal-avail').click(function(e){
        if(e.target.id == "notify" || !$(e.target).closest(".modal-dialog")){


            $(this).modal('hide');
        }
    });

    $('#edit-modal-avail').on('shown.bs.modal', function() {
        $("body").addClass("modal-open");
    });

    $('#edit-modal-avail').on('hide.bs.modal', function() {
        $("body").removeClass("modal-opend");
    })


});
function getAvailConfig(){
    $.ajax({
        url: 'index.php?route=extension/module/avail/getConfig', // получаем настройки
        type: 'post',
        data: '',
        dataType: 'json',
        success:function(json){

            all_button_id = json.all_button_id;    //кнопка купить на миниатюрах
            block_product = json.block_product;    // блок продукта на миниатюрах
            status = json.button; // включен модуль по опциям или без
            avail_default = json.avail_default; // актывный шаблон базовый или нет
            text = json.text; // название кнопки
            button_avail_help = json.button_avail_help; // подсказка при наведении
            blok_in_productpage = json.avail_block_option_productpage; // блок данных по родукту страница товара
            button_cart_productpage    = json.avail_button_cart_productpage; // слас кнопки купить на странице товара
            avail_options_status = json.avail_options_status; // работать с опциями или без
            avail_button_other_productpage = json.avail_button_other_productpage; // дополнительная кнопка(например быстрый заказ)
            // если модуль включен

            avail_background_button_open_notify = json.avail_background_button_open_notify;
            avail_background_button_send_notify = json.avail_background_button_send_notify;
            avail_border_button_open_notify = json.avail_border_button_open_notify;
            avail_border_button_send_notify = json.avail_border_button_send_notify;
            avail_icon_open_notify = json.avail_icon_open_notify;
            avail_icon_send_notify = json.avail_icon_send_notify;
            avail_text_button_open_notify = json.avail_text_button_open_notify;
            avail_text_button_send_notify = json.avail_text_button_send_notify;
            button_type = json.avail_button_type;
            avail_button_athepage_class = json.avail_button_athepage_class;
            avail_customer_class = json.avail_customer_class;
            /* добавляем стили на страницу */

            if(avail_customer_class.length !== 0) {
                $(document).find("footer").after("<style> " + avail_customer_class + "</style>");
            }

            /*скрывать или нет название кнопки на мобильных устройствах*/
            json.avail_buttom_hide_mob = 0;
            if(json.avail_buttom_hide_mob == 1){
                avail_buttom_hide_mob = 'hidden-xs hidden-sm hidden-md';;
            } else {
                avail_buttom_hide_mob = '';
            }
            ButtonAtherPage(json,button_type);
            checkQuantityJS(json,button_type);
            ButtonProductPage(json,button_type);
            ButtonCommparePage(json,button_type);
            ButtonWishlistPage(json,button_type);

            //открытие модального окна для миниатюр
            $( document ).on( "click", ".notify_compare", function(e) {
                $("#edit-modal-avail").modal();

                product_id_avail = $(this).data('product_id_avail');
                language_id = $(this).data('language_id');

                $.ajax({
                    cache: false,
                    type: 'POST',
                    url: 'index.php?route=extension/module/avail/openForm',
                    data: {product_id:product_id_avail, language_id:language_id},
                    success: function(data){
                        $('.avanoti .modal-content').html(data);
                    }
                });
                $('body').removeClass('modal-open');
                $("body").css({ 'padding-right' : ''});
            });
        }
    });
}


function checkQuantityJS(json,button_type) {
    var avail_input_quantity = $('#input-quantity');
    //$( document ).on( "change", "#input-quantity", function() {
    if($('#input-quantity').length > 0) {
        console.log('55555');
        $('#input-quantity').on("input", function(e) {
            console.log('77777');
            var avail_quantity = $('#input-quantity').val();
            var avail_product_id = $('input[name=\'product_id\']').val();

            var options = $(json.avail_block_option_productpage + " input[type=\'hidden\'], " + json.avail_block_option_productpage + "  input[type=\'radio\']:checked, " + json.avail_block_option_productpage + " input[type=\'checkbox\']:checked, " + json.avail_block_option_productpage + " select," + json.avail_block_option_productpage + " img").serialize();

            var data_send = options + '&quantity=' + avail_quantity + '&product_id=' + avail_product_id;

            if (avail_quantity != 'underfined' && avail_product_id != 'underfined') {

                if (avail_quantity > 0) {

                    $.ajax({
                        url: 'index.php?route=extension/module/avail/checkQuantity',
                        type: 'POST',
                        dataType: 'json',
                        // data:{
                        //     'quantity':avail_quantity,
                        //     'product_id':avail_product_id,
                        //     'options':options,
                        //     'opt':opt
                        // },
                        data: data_send,
                        success: function (json) {

                            if (json['command']) {

                                if (json['command'] == 'replace') {
                                    $(json['btn_cart']).addClass("hidden");
                                    $(".notify_product").removeClass("hidden");
                                    $("#input-desired_quantity").val(avail_quantity);
                                    console.log($("#input-desired_quantity").val())
                                } else if (json['command'] == 'not_replace') {
                                    $(json['btn_cart']).removeClass("hidden");
                                    $(".notify_product").addClass("hidden");
                                    $("#input-desired_quantity").val(1);
                                }
                            }
                            //console.log(json)
                        }
                    });

                }
            }

        });
    }
}

function ButtonAtherPage(json,button_type) {

    if (json.button == '1') {


        /*********** размещение кнопки уведомить на страница категории, поиска, модулей **********/
        // Если включена настройка шаблон по умолчанию
        if (json.avail_default == '1') {

            json.block_product = '.product-thumb'; // блок продукта на миниатюрах
            if ($(this).has('.notify').length === 0) {
                $(json.block_product).each(function () { // проходимо по всих товарах
                    product_id = $(this).find('.aval-product-id').text();//получаем id товара
                    quantity_avl = $(this).find('.aval-product-quantity').text();//получаем количество товара
                    if (product_id) {
                        console.log(button_type);
                        switch (button_type) {
                            case '0':
                                $(this).find("button[onclick^='cart.add']").after("<button  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +" hidden'> <i class='fa fa-envelope'></i><span class='"+avail_buttom_hide_mob+"'> &nbsp;" + json.text + "</span></button>");// добавляем кнопку уведомить
                                break;
                            case '2':
                                $(this).find("button[onclick^='cart.add']").after("<input  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +"  hidden' value=" + json.text + "><span class='"+avail_buttom_hide_mob+"'></span>");// добавляем кнопку уведомить
                                break;
                            case '1':
                                $(this).find("button[onclick^='cart.add']").after("<a  tple='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +"  hidden'><i class='fa fa-envelope'></i><span class='"+avail_buttom_hide_mob+"'> &nbsp;" + json.text + "</span></a>");
                                break;
                        }


                        $('.notify').css({
                            "background": "#" + json.avail_background_button_open_notify,
                            "border": "1px solid #" + json.avail_border_button_open_notify,
                            "color": "#" + json.avail_text_button_open_notify
                        }).find('i').removeClass('fa-envelope').addClass(json.avail_icon_open_notify);


                        $(this).find("button[onclick^='cart.add']").addClass("cart-avail" + product_id); // добавляем класс для кнопки купить
                        // если есть еще кнопка которую надо скрыть например Заказ в один клик
                        if (json.avail_button_other_productpage) {
                            $(this).find(json.avail_button_other_productpage).addClass("other-avail-prod" + product_id); // добавляем
                        }
                        // если количество товара меньше или 0
                        if (quantity_avl < 1) {
                            $(".cart-avail" + product_id).addClass("hidden");//скрываем кнопку купить
                            if (json.avail_button_other_productpage) {
                                $(".other-avail-prod" + product_id).addClass("hidden"); //скрываем дополнительную кнопку
                            }
                            $(".notify[data-product_id_avail='" + product_id + "']").removeClass("hidden");    //отображаем кнопку уведомить

                        }
                    }


                });
            }
        } else {
            // Если выбран кастомный шаблон
            $(json.block_product).each(function () { // проходимо по всих товарах
                if ($(this).has('.notify').length === 0) {
                    product_id = $(this).find('.aval-product-id').text();//получаем id товара
                    quantity_avl = $(this).find('.aval-product-quantity').text();//получаем количество товара
                    if (product_id) {
                        switch (button_type) {
                            case '0':
                                $(this).find(json.all_button_id).after("<button  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +"  hidden'> <i class='fa fa-envelope'></i><span class='"+avail_buttom_hide_mob+"'> &nbsp;" + json.text + "</span></button>");// добавляем кнопку уведомить
                                break;
                            case '2':
                                $(this).find(json.all_button_id).after("<input  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +"  hidden' value=" + json.text + "><span class='"+avail_buttom_hide_mob+"'></span>");// добавляем кнопку уведомить
                                break;
                            case '1':
                                $(this).find(json.all_button_id).after("<a  tple='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify "+ json.avail_button_athepage_class +"  hidden'><i class='fa fa-envelope'></i><span class='"+avail_buttom_hide_mob+"'> &nbsp;" + json.text + "</span></a>");
                                break;
                        }

                        $('.notify').css({
                            "background": "#" + json.avail_background_button_open_notify,
                            "border": "1px solid #" + json.avail_border_button_open_notify,
                            "color": "#" + json.avail_text_button_open_notify
                        }).find('i').removeClass('fa-envelope').addClass(json.avail_icon_open_notify);

                        // добавляем кнопку уведомить
                        //  $(this).find(json.all_button_id).before("<button  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify hidden' value=" + json.text + "> <i class='fa fa-envelope'></i><span class='"+avail_buttom_hide_mob+"'></i> &nbsp;" + json.text + "</span></button>");
                        // добавляем класс для кнопки купить
                        $(this).find(json.all_button_id).addClass("cart-avail" + product_id);
                        // если есть еще кнопка которую надо скрыть например Заказ в один клик
                        if (json.avail_button_other_productpage) {
                            $(this).find(json.avail_button_other_productpage).addClass("other-avail-prod" + product_id); // добавляем
                        }
                        // если количество товара меньше или 0
                        if (quantity_avl < 1) {
                            $(".cart-avail" + product_id).addClass("hidden");//скрываем кнопку купить

                            if (json.avail_button_other_productpage) {
                                $(".other-avail-prod" + product_id).addClass("hidden"); //скрываем дополнительную кнопку
                            }
                            $(".notify[data-product_id_avail='" + product_id + "']").removeClass("hidden");    //отображаем кнопку уведомить
                        }
                    }
                }
            });
        }
    }
}

function ButtonProductPage(json,button_type) {

    $(json.avail_button_cart_productpage).after("<button type='button' target='#myModal' data-toggle='modal'  class='notify_product "+json.avail_button_product_class+" hidden'>"+json.text+"</button>");

    // находим опции на товаре и создаем масив

    var mass = Array.prototype.slice.call($(json.avail_block_option_productpage + " input[type='radio'], " + json.avail_block_option_productpage + " input[type='checkbox']," + json.avail_block_option_productpage + " select"));




    // если есть опции и настройка работы по опциям включена и модуль включен
    if((mass.length !== 0) && (json.avail_options_status == '1') && (json.button == '1')) {

        // получаем масив с опциями
        var objoption = json.avail_block_option_productpage + ' input,'+json.avail_block_option_productpage + ' select,' + json.avail_block_option_productpage + ' img';

       // что бы запрос на количество опции оправлялся сразу при загрузке страницы. тот случай когда опция выбрана по умолчанию
        jQuery(document).ready(function(){
            // получаем значения опции
            var options = $(json.avail_block_option_productpage + " input[type=\'hidden\'], " + json.avail_block_option_productpage + "  input[type=\'radio\']:checked, " + json.avail_block_option_productpage + " input[type=\'checkbox\']:checked, " + json.avail_block_option_productpage + " select," + json.avail_block_option_productpage + " img").serialize();
            var avail_quantity = $('#input-quantity').val();

            var data_send = options + '&quantity=' + avail_quantity;
            $.ajax({
                url: 'index.php?route=extension/module/avail/getoptionsquantity',
                type: 'post',
                data: data_send
            })
                .success(function(response){

                    if (response == "false") {
                        $(json.avail_button_cart_productpage).addClass("hidden");
                        if(json.avail_button_other_productpage){
                            $(json.avail_button_other_productpage).addClass("hidden");
                        }
                        $(".notify_product").removeClass("hidden");

                    } else {
                        $(json.avail_button_cart_productpage).removeClass("hidden");
                        if(json.avail_button_other_productpage){
                            $(json.avail_button_other_productpage).removeClass("hidden");
                        }
                        $(".notify_product").addClass("hidden");

                    }
                })
                .error(function(response) {
                });

        });


        // если опция изменена
        $(objoption).on('change', function() {
            // получаем значения опции
            var options = $(json.avail_block_option_productpage + " input[type=\'hidden\'], " + json.avail_block_option_productpage + "  input[type=\'radio\']:checked, " + json.avail_block_option_productpage + " input[type=\'checkbox\']:checked, " + json.avail_block_option_productpage + " select," + json.avail_block_option_productpage + " img").serialize();
            var avail_quantity = $('#input-quantity').val();

            var data_send = options + '&quantity=' + avail_quantity;
            $.ajax({
                url: 'index.php?route=extension/module/avail/getoptionsquantity',
                type: 'post',
                data: data_send
            })
                .success(function(response){

                    if (response == "false") {
                        $(json.avail_button_cart_productpage).addClass("hidden");
                        if(json.avail_button_other_productpage){
                            $(json.avail_button_other_productpage).addClass("hidden");
                        }
                        $(".notify_product").removeClass("hidden");

                    } else {
                        $(json.avail_button_cart_productpage).removeClass("hidden");
                        if(json.avail_button_other_productpage){
                            $(json.avail_button_other_productpage).removeClass("hidden");
                        }
                        $(".notify_product").addClass("hidden");

                    }
                })
                .error(function(response) {
                });
        });
    } else if(json.button == '1'){
        // если модуль включен без опций
        product_id = $('.aval-product-page-id').text();//получаем id товара
        quantity_avail =  $('.aval-product-page-quantity').text();//получаем количество товара

        if(quantity_avail < 1){
            $(json.avail_button_cart_productpage).addClass("hidden");
            if(json.avail_button_other_productpage){
                $(json.avail_button_other_productpage).addClass("hidden");
            }
            $(".notify_product").removeClass("hidden");
        }
    }
    $('.notify_product').css({
        "background": "#" + json.avail_background_button_open_notify,
        "border": "1px solid #" + json.avail_border_button_open_notify,
        "color": "#" + json.avail_text_button_open_notify
    }).find('i').removeClass('fa-envelope').addClass(json.avail_icon_open_notify);
}
function   ButtonCommparePage(json,button_type){

    if (json.button == '1') {
        // Если выбран кастомный шаблон
        json.block_product = $('input[onclick^="cart.add"]');

        $(json.block_product).each(function () { // проходимо по всих товарах
            console.log($(this));
            var product_id = $(this).siblings('.aval-product-id').text();
            var quantity_avl =  $(this).siblings('.aval-product-quantity').text();
            $(this).after("<input type='button'  data-product_id_avail='" + product_id + "' value='" + json.text + "' class='button  notify hidden' >")
            $(this).addClass("cart-avail-" + product_id);

            if (quantity_avl < 1) {
                $(".cart-avail-" + product_id).addClass("hidden");//скрываем кнопку купить

                if (json.avail_button_other_productpage) {
                    $(".other-avail-prod" + product_id).addClass("hidden"); //скрываем дополнительную кнопку
                }
                $(".notify[data-product_id_avail='" + product_id + "']").removeClass("hidden");    //отображаем кнопку уведомить
            }

        });
        $('.notify').css({
            "background": "#" + json.avail_background_button_open_notify,
            "border": "1px solid #" + json.avail_border_button_open_notify,
            "color": "#" + json.avail_text_button_open_notify
        })
    }
}
function   ButtonWishlistPage(json,button_type){

    if (json.button == '1') {

        // Если выбран кастомный шаблон
        json.block_product = $('#account-wishlist button[onclick^="cart.add"]');

        $(json.block_product).each(function () { // проходимо по всих товарах

            var product_id = $(this).siblings('.aval-product-id').text();
            var quantity_avl =  $(this).siblings('.aval-product-quantity').text();

            $(this).after("<button  type='button'  title='" + json.button_avail_help + "' target='#myModal' data-toggle='tooltip' data-product_id_avail='" + product_id + "' class='notify hidden btn btn-primary'> <i class='fa fa-envelope'></i></i></button>");// добавляем кнопку уведомить

            $(this).addClass("cart-avail-" + product_id);

            if (quantity_avl < 1) {
                $(".cart-avail-" + product_id).addClass("hidden");//скрываем кнопку купить

                if (json.avail_button_other_productpage) {
                    $(".other-avail-prod" + product_id).addClass("hidden"); //скрываем дополнительную кнопку
                }
                $(".notify[data-product_id_avail='" + product_id + "']").removeClass("hidden");    //отображаем кнопку уведомить
            }

        });
    }
}

var captchaModal;
var renderCaptcha = function () {
    if (typeof grecaptcha != 'undefined') {
        $(document).ready(function () {
            setTimeout(function(){
                $("button.notify_product, button.notify").click(function(){
                    $(".block-with-avail-captcha").removeClass("hidden");
                    grecaptcha.reset(captchaModal);
                });
            },2000);
            $('.g-recaptcha').each(function () {
                $(this).html('');
                if($(this).attr('id') == 'avail-captcha'){
                    captchaModal = grecaptcha.render($(this)[0], { sitekey: $(this).attr('data-sitekey') });

                }else{
                    var widgetId = grecaptcha.render($(this)[0], { sitekey: $(this).attr('data-sitekey') });

                }
            });
        });
    }
};