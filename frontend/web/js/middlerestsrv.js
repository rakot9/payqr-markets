var merchant_id = "094711-13811";


function initButton(callback, place){
    
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
        
        success: function(response)
        {
            console.log(response);
            
            if(typeof response ==  "object")
            {
                data = response;

                if(typeof data.button == "undefined")
                {
                    console.log("Промежуточный сервер вернул данные в некорректном формате!");
                    
                    return false;
                }
                else
                {
                    return data.button;
                }

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

function callbackBasketButton(data)
{
    if(data.data)
    {        
        var button = $('button[class=payqr-button]').attr(data.data);
        console.log(data.data);
    }
}
    
function setCart()
{
    var basketItems = initButton("callbackBasketButton", "cart");
    
    console.log(basketItems);
    
    if(typeof basketItems == "Array")
    {
        console.log("Basket items ia array");
    }
}

$(function(){
    setCart();
});