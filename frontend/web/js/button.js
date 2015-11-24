/**
* Переменная хранит часть имени триггера, которая отображает эелменты кнопки
*/
var buttonShowTrigger = "payqr-button_showinplace";

/**
* Часть сайта в которых отображается кнопка
*/
var buttonPlace  = new Array("cart", "product", "category");

/**
* Часть имени элементов, которые будем скрывать, при условии, что триггер buttonPlace + buttonShowTrigger 
* выставлен, как "Нет"
*/
var buttonFields = new Array("payqr-button_color", "payqr-button_form", "payqr-button_shadow", "payqr-button_gradient", "payqr-button_font_trans", "payqr-button_font_width", "payqr-button_text", "button_height", "button_width");


$(function(){

	$("select[name$='payqr-button_showinplace']").on("change", function(){

		var selectedEntityName = $(this).attr("name");

		if(typeof selectedPlace != "udnefined")
		{
			var placeName = $(this).attr("name");

			var iNumPos = placeName.indexOf("payqr-button_showinplace");

			if(iNumPos == -1)
			{
				return;
			}

			placeName = placeName.substr(0, iNumPos);

			//Проверяем, найденное место присутствует в массиве buttonPlace

			if(buttonPlace.indexOf(placeName) == -1)
			{
				return;
			}

		
			if("no" == $(this).val())
			{
				for(var iButtonParam=0; iButtonParam < buttonFields.length; iButtonParam++)
				{
					$("[name='" + placeName + buttonFields[iButtonParam] + "']").closest('div[class^=row]').hide();
				}
			}
			
			if("yes" == $(this).val())
			{
				for(var iButtonParam=0; iButtonParam < buttonFields.length; iButtonParam++)
				{
					$("[name='" + placeName + buttonFields[iButtonParam] + "']").closest('div[class^=row]').show();
				}
			}
		}
		
	})
	
	//Производим скрытие элементов
	 for(var iPlaceName=0; iPlaceName < buttonPlace.length; iPlaceName++)
	 {
		if("no" == $("select[name='" + buttonPlace[iPlaceName] + buttonShowTrigger + "'] option:selected").val())
		{
			for(var iButtonParam=0; iButtonParam < buttonFields.length; iButtonParam++)
			{
				$("[name='" + buttonPlace[iPlaceName] + buttonFields[iButtonParam] + "']").closest('div[class^=row]').hide();
			}
		}
	 }
});