var payqrButton = {

    merchant_id : "",
    token : "",
    userData: {},
    button: {},

    init: function(merchant_id, token){
        this.merchant_id = merchant_id;
        this.token = token;
        this.createButton();
    },

    initButtonParams: function(){
        this.setUserData();
        this.button['data-scenario']   = 'pay';
        this.button['data-merchId']    = this.merchant_id;
        this.button['data-amount']     = this.getTotal();
        this.button['data-userdata']   = JSON.stringify(this.getUserData());
        this.button['data-cart']       = JSON.stringify(this.getDataCart());
        this.button['data-orderIdRequired'] = 'required';

        //устанавливаем в локальное хранилище
        sessionStorage.setItem('button', JSON.stringify(this.button));
    },

    createButton: function(){

        /**
         * Перед созданием кнопки проинициализируем ее параметры
         */
        this.initButtonParams();

        if($("#create_order").length > 0)
        {
            if($("button[class*=payqr-button]").length > 0)
            {
                this.setButtonParams();

                if(!this.checkPaymentIsSelected())
                {
                    $("button[class*=payqr-button]").hide();
                }
                else
                {
                    $("button[class*=payqr-button]").show();
                }
                return;
            }

            $("#create_order").after("<button class=\"payqr-button\">Купить быстрее</button>");

            this.setButtonParams();

            /**
             * Определим, выбрана ли платежная система
             */
            if(!this.checkPaymentIsSelected())
            {
                $("button[class*=payqr-button]").hide();
            }
        }
    },

    setButtonParams: function(){
        if($('button[class^=payqr-button]').length > 0)
        {
            for(var buttonParamKey in this.button)
            {
                if(this.button.hasOwnProperty(buttonParamKey))
                {
                    $('button[class^=payqr-button]').attr(buttonParamKey, this.button[buttonParamKey]);
                }
            }
        }
    },

    getUserData: function(){
        return this.userData;
    },

    setUserData: function(){

        var sessStoreButton = sessionStorage.getItem('button');

        try {
            this.button = JSON.parse(sessStoreButton);
            this.userData = (typeof this.button['data-userdata'] == "object" )? this.button['data-userdata'] : JSON.parse(this.button['data-userdata']);
        }
        catch(e)
        {
            console.error("Не смогли получить данные");
        }

        if($("#client_name").length > 0)
        {
            var name = $("#client_name").val().replace(/( ){1,}/g, " ").split(" ");

            if(typeof name[1] !== "undefined" && name[1].length >0)
            {
                this.userData.firstName = name[1];
            }
            if(typeof name[0] !== "undefined" && name[0].length >0)
            {
                this.userData.lastName = name[0];
            }
            if(typeof name[2] !== "undefined" && name[2].length >0)
            {
                this.userData.middleName = name[2];
            }
        }

        if($("#client_phone").length > 0 && $("#client_phone").val().length > 0)
        {
            this.userData.phone = $("#client_phone").val();
        }

        if($("#client_email").length > 0 && $("#client_email").val().length > 0)
        {
            this.userData.email = $("#client_email").val();
        }

        if($("#shipping_address_zip").length > 0 && $("#shipping_address_zip").val().length > 0)
        {
            this.userData.zip = $("#shipping_address_zip").val();
        }

        if($("#shipping_address_city").length > 0 && $("#shipping_address_city").val().length > 0)
        {
            this.userData.city = $("#shipping_address_city").val();
        }

        if($("#shipping_address_address").length > 0 && $("#shipping_address_address").val().length > 0)
        {
            this.userData.address = $("#shipping_address_address").val();
        }

        if($("#shipping_address_no_delivery").length < 0)
        {
            this.userData.city = null;
            this.userData.address = null;
        }

        if($("#delivery input:checked").length > 0)
        {
            this.userData.deliveryId = $("#delivery input:checked").val();
        }

        if($("#payment input:checked").length > 0)
        {
            this.userData.paymentId = $("#payment input:checked").val();
        }

        this.userData.token = this.token;

        this.userData.merchId = this.merchant_id;
    },

    checkPaymentIsSelected: function(){
        if(($("#order_payment_gateway_id_" + $("#payment_gateways input:checked").attr("data-payment-id")).length > 0 &&
            $("#order_payment_gateway_id_" + $("#payment_gateways input:checked").attr("data-payment-id")).closest('tr').html().toLowerCase().match(/payqr/i))
            || ($("#payment input:checked").length > 0 && $("#payment input:checked").closest('tr').html().toLowerCase().match(/payqr/i))
            )
        {
            return true;
        }
        return false;
    },

    getTotal: function (){
        var total = 0;
        try {
            var cart = $.parseJSON($.cookie('cart'));
            if (cart)
            {
                $.each(cart.order_lines, function(index, order_line){
                    total = cart.total_price;
                })
            }
        }
        catch(e){
            console.error("Не смогли обработать данные в корзине!");
        }
        return total;
    },

    getDataCart: function(){
        var payqr_data_cart = [];
        try {
            var cart = $.parseJSON($.cookie('cart'));
            if (cart)
            {
                $.each(cart.order_lines, function(index, order_line){
                    var item = {};
                    item['article']  = order_line.variant_id;
                    item['name']     = order_line.title;
                    item['quantity'] = order_line.quantity;
                    item['amount']   = order_line.sale_price * order_line.quantity;
                    item['imageUrl'] = order_line.image_url;
                    payqr_data_cart.push(item);
                })
            }
        }
        catch(e){
            console.error("Не смогли обработать данные в корзине!");
        }
        return payqr_data_cart;
    },

    clearUserData: function(){
        return {
            "data-userdata":{
                                "firstName":"",
                                "lastName":"",
                                "middleName":"",
                                "phone":"",
                                "email":"",
                                "zip":"",
                                "city":"",
                                "address":"",
                                "token":"",
                                "merchId":"",
                                "deliveryId":"",
                                "paymentId":""
            }
        }
    }
}

$(function(){
    /**
     * Производим обработку поступившего ответа от сервера
     */
    if(typeof payQR  !== "undefined"){

        payQR.onPaid(function(data) {

            var message = "Ваш заказ #" + data.orderId + " успешно оплачен на сумму: " + data.amount + "! ";

            try{
                payqrUserData = $.parseJSON(data.userData);

                if(typeof payqrUserData !== "undefined" && typeof payqrUserData.new_account !== "undefined" && 
                   (payqrUserData.new_account == true || payqrUserData.new_account == "true"))
                {
                    message += " Администратор сайта свяжется с вами в самое ближайшее время!";
                }

                alert(message);

                console.log(data);

                redirectUrl = window.location.origin;

                //здесь производим очистку корзины
                $('#cartform a[class=js-cart_item-delete]').each(function(key, data)
                {
                        $.ajax({
                                type: 'post',
                                url: data.href,
                                data: {_method:'delete'}
                        });
                });
                
                window.location.replace( redirectUrl + '/client_account/login' );
            }
            catch(e)
            {
                alert("Возникли ошибки при обработке данных!");
            }
        });
    }

    /**
     * Перерисовываем кнопку при изменении параметров "оформления заказа"
     */
    $(document).ajaxComplete(function(event, xhr, settings){
        if(settings.url.match(/payment\/for_order/i))
        {
            window.sessionStorage.setItem('button', JSON.stringify(payqrButton.clearUserData()));
            payqrButton.init(merchant_id, insales);
        }
    });

    payqrButton.init(merchant_id, insales);
    /**
     * Событие на обновление таблицы
     */
    $('#order_form, #contacts, #delivery, #payment').on('change', function(){
        if(this.id == "contacts")
        {
            window.sessionStorage.setItem('button', JSON.stringify(payqrButton.clearUserData()));
        }
        payqrButton.init(merchant_id, insales);
    });

    /**
     * При нажатии на скнопку "Продолжить"
     */
    $('#contacts, #delivery, #payment').submit(function(){
        if(this.id == "contacts")
        {
            window.sessionStorage.setItem('button', JSON.stringify(payqrButton.clearUserData()));
        }
        payqrButton.init(merchant_id, insales);
    });
});