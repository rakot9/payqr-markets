function initButton(callback, place){
    
    if( typeof merchant_id == "undefined")
    {
        return false;
    }
    
    if(callback && $.isFunction(callback))
    {
        console.log(callback + " isn't function!");
        
        return false;
    }
    
    if(!place)
    {
        return false;
    }
    
    $.ajax({
        
        type: 'GET',
        
        url: 'http://insales.payqr-sites.qrteam.ru/?r=api/get&HTTP_X_API_KEY=EW5ERdsfwref23&type=button&callback=' + callback + '&merchant_id=' + merchant_id + '&place=' + place,
        
        dataType: "jsonp",
        
        jsonpCallback: callback,
        
        complete: function()
        {
            console.log("complete");
        },
        
        success: function(response)
        {
            console.log("success");
            
            if(typeof response ==  "object")
            {
                data = response;

//                if(typeof data.button == "undefined")
//                {
//                    console.log("Промежуточный сервер вернул данные в некорректном формате!");
//                    return false;
//                }
//                else
//                {
//                    return data.button;
//                }

                return "";
            }
            if($.parseJSON(response))
            {
                try{
                    var data = $.parseJSON(response);

                    if(typeof data.cart == "undefined" || data.cart.length == 0)
                    {
                        console.log("Промежуточный сервер вернул данные в некорректном формате!");
                        return false;
                    }

                    return data.cart;
                }
                catch(e){
                    console.log("Не смогли получить данные с промежуточного сервера в нужном формате!");
                    return false;
                }
            }
            return false;
        }
    });
}

function callbackButton(data)
{
    var button = $('button[class^=payqr-button]');
    
    if(data.data && true == data.display)
    {
        button.show();
        
        if(typeof data.data.class != "undefined")
        {
            button.addClass(data.data.class.join(" "));
        }
        if(typeof data.data.style != "undefined")
        {
            button.attr("style", data.data.style.join(";"));
        }
        if(typeof data.data.attr != "undefined")
        {
            button.attr(data.data.attr);
        }
    }
    else
    {
        button.hide();
    }
}
    
function setCart(buttonPlace)
{
    var basketItems = initButton("callbackButton", buttonPlace);
    
    if(typeof basketItems == "Array")
    {
        console.log("Basket items ia array");
    }
}

function setProduct()
{
    initButton("callbackButton", "product");
}

function RefreshDataCart()
{
    var button = $('button[class^=payqr-button]');
    
    var cart = $.parseJSON($.cookie('cart'));
    
    var price  = 0;
    
    var count = 0;    
    
    var payqr_data_cart = [];
    
    if (cart) {

        price = cart.total_price;
        
        $.each(cart.order_lines, function(index, order_line) 
        {
            var item = {};
            
            count += order_line.quantity;
            
            item['article'] = order_line.variant_id;
            item['name'] = order_line.title;
            item['quantity'] = order_line.quantity;
            item['amount'] = order_line.sale_price * order_line.quantity;
            item['imageUrl'] = order_line.image_url;
            
            payqr_data_cart.push(item);
        });
    }
    
    button.attr('data-amount', price);
    
    button.attr('data-cart', JSON.stringify(payqr_data_cart));
}

$(function(){
    if($("button[class*=payqr-button]").length > 0)
    {
        if($("#cartform").length)
        {
            setCart("cart");
        }
        else if($("div[class=product_preview-preview]").length)
        {
            setCart("category")
        }
        else if($("div[class=product_preview-preview]").length==0 && $("div[class=product_preview-preview]").length ==0)
        {
            setCart("product");
        }
    }
    
    $(document).ajaxComplete(function(event, xhr, settings){
        
        if(settings.url.indexOf('/cart_items/') != -1)
        {
            RefreshDataCart();
        }
    });
    
    if(typeof payQR  !== "undefined")
    {
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
});