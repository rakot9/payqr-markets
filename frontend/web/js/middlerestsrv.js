function initBasketButton(callback){
    
    if(callback && $.isFunction(callback))
    {
        console.log(callback + " isn't function!");
        
        return false;
    }
    
    $.ajax({
        
        type: 'GET',
        
        url: 'http://insales.payqr-sites.qrteam.ru/?r=api/get&HTTP_X_API_KEY=EW5ERdsfwref23',
        
        dataType: "jsonp",
        
        jsonpCallback: callback,
        
        success: function(response)
        {
            console.log(response);
            
            try{
                data = $.parseJSON(response);

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
            return false;
        }
    });
}

function callbackBasketButton(data)
{
    console.log(data.data);
}
    
function setCart()
{
    var basketItems = initBasketButton("callbackBasketButton");
    
    console.log(basketItems);
    
    if(typeof basketItems == "Array")
    {
        console.log("Basket items ia array");
    }
    
    var button = $('button[class=payqr-button]');
}

$(function(){
    setCart();
});