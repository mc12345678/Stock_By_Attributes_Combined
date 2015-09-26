// $Id: products_with_attributes_stock_ajax.js 389 2008-11-14 16:02:14Z hugo13 $
//Stock by Attributes 1.5.4 (supports jquery-1.10.2.min.js)

$(document).ready(function() { 
    // bind form using ajaxForm 
    $('#pwas-search').ajaxForm({ 
        // target identifies the element(s) to update with the server response 
        target: '#pwa-table',
        url: 'products_with_attributes_stock_ajax.php', 
        success:  addEvent
 
        // success identifies the function to invoke when the server response 
        // has been received; here we apply a fade-in effect to the new content 
    });
});

$(document).ready(function() { 
    // bind form using ajaxForm 
    $('#store').ajaxForm({ 
        // target identifies the element(s) to update with the server response 
        target: '#hugo1', 
        success: saved
 
        // success identifies the function to invoke when the server response 
        // has been received; here we apply a fade-in effect to the new content 
    }); 
});


$(document).ready(function(){
//TODO: Look at the URL: should this be a random generator entry	
    $("#btnrandom").click(function(e){
        e.preventDefault(); // Normales Submit unterdrücken

        $.ajax({ // AJAX Request auslösen
            type: "POST",
            url: '/data/random/5a0bc3836e07a7be06a2fc3109b9d9daaffeafda/1',
            dataType: 'json',
            global: 'false',
            success: processJason
        });
    });
    
    $("#loading").hide();    // Das Loding Element verstecken
    
    //Quantity
    $(".stockAttributesCellQuantity").click(function(event){
        var $tgt = $(event.target);
        var $id = this.id;
        var $inner = this.innerHTML;
        if (!$tgt.is('input')) {
            if(!this.hasEventHander){
            var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
            this.innerHTML = $newLi;
                this.hasEventHander = true;
            }
        }        
    })
    
    //Sort
    $(".stockAttributesCellSort").click(function(event){
        var $tgt = $(event.target);
        var $id = this.id;
        var $inner = this.innerHTML;
        if (!$tgt.is('input')) {
            if(!this.hasEventHander){
            var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
            this.innerHTML = $newLi;
                this.hasEventHander = true;
            }
        }        
    })
    
    //stockAttributesCellCustomid
    $(".stockAttributesCellCustomid").click(function(event){
        var $tgt = $(event.target);
        var $id = this.id;
        var $inner = this.innerHTML;
        if (!$tgt.is('input')) {
            if(!this.hasEventHander){
            var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
            this.innerHTML = $newLi;
                this.hasEventHander = true;
            }
        }        
    })

    //stockAttributesCellTitle
    $(".stockAttributesCellTitle").click(function(event){
        var $tgt = $(event.target);
        var $id = this.id;
        var $inner = this.innerHTML;
        if (!$tgt.is('input')) {
            if(!this.hasEventHander){
            var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="25"/>';
            this.innerHTML = $newLi;
                this.hasEventHander = true;
            }
        }        
    })
    
});

//stockAttributesCellQuantity
function addEvent() {
 $('.stockAttributesCellQuantity').each(
            function(){
                if(!this.hasEventHander)
                    $(this).click(function(event){    
                    /*/ Our Eventhanderl /*/    
                    var $tgt = $(event.target);
                    var $id = this.id;
                    var $inner = this.innerHTML;
                    if (!$tgt.is('input')) {
                        var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
                        this.innerHTML = $newLi;
                        //this.unbind("click");
                    }        
                });
                this.hasEventHander = true;
            }
        );
}

//stockAttributesCellSort
function addEvent() {
	$('.stockAttributesCellSort').each(
			function(){
				if(!this.hasEventHander)
					$(this).click(function(event){    
					/*/ Our Eventhanderl /*/    
	                var $tgt = $(event.target);
	                var $id = this.id;
	                var $inner = this.innerHTML;
	                if (!$tgt.is('input')) {
	                	var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
	                    this.innerHTML = $newLi;
	                    //this.unbind("click");
	                }        
					});
	           this.hasEventHander = true;
			}
	);
}

//stockAttributesCellCustomid
function addEvent() {
	$('.stockAttributesCellCustomid').each(
			function(){
				if(!this.hasEventHander)
					$(this).click(function(event){    
					/*/ Our Eventhanderl /*/    
	                var $tgt = $(event.target);
	                var $id = this.id;
	                var $inner = this.innerHTML;
	                if (!$tgt.is('input')) {
	                	var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="8"/>';
	                    this.innerHTML = $newLi;
	                    //this.unbind("click");
	                }        
					});
	           this.hasEventHander = true;
			}
	);
}

//stockAttributesCellTitle
function addEvent() {
	$('.stockAttributesCellTitle').each(
			function(){
				if(!this.hasEventHander)
					$(this).click(function(event){    
					/*/ Our Eventhanderl /*/    
	                var $tgt = $(event.target);
	                var $id = this.id;
	                var $inner = this.innerHTML;
	                if (!$tgt.is('input')) {
	                	var $newLi = '<input type="text" name="' + $id + '" id="' + $id + '" value="' + $inner + '" size="25"/>';
	                    this.innerHTML = $newLi;
	                    //this.unbind("click");
	                }        
					});
	           this.hasEventHander = true;
			}
	);
}

function saved(responseText, statusText)  { 
    alert('status: ' + statusText + '\n\nresponseText: \n' + responseText ); 
}