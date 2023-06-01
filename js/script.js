$(document).ready(function(){

	var fixedContent = $('.fixedcontent'),
		wrapper = $('.wrapper'),
		icon = $('.menu-icon'),
		iconClose = $('.icon-close');

	icon.click(function(){
		if (wrapper.hasClass('active_menu')) {
			wrapper.removeClass('active_menu');
			fixedContent.removeClass('active_q');
		}else{
			wrapper.addClass('active_menu');
			fixedContent.addClass('active_q');
			
		}
	});
	

	$("*").on("click",function(event){
		if ( wrapper.hasClass('active_menu') ) {
			
		} else {
			if($(event.target).parents(".list-menu-responsice").length>0)
			{
				// wrapper.removeClass('active_menu');
				// fixedContent.removeClass('active_q');
				alert("Aaaaaaa");	
			}else
			{
				
			}
		}
	});


	$('.icon-close , .none-opcitya').click(function(){
		wrapper.removeClass('active_menu');
		fixedContent.removeClass('active_q');
	});

	// ***********************
	// custame select option
	// **********************
	if ($('.styled-select').length > 0) {
		$('.styled-select').each(function(){
		    var $this = $(this), numberOfOptions = $(this).children('option').length;
		  
		    $this.addClass('select-hidden'); 
		    $this.wrap('<div class="select"></div>');
		    $this.after('<div class="select-styled"></div>');

		    var $styledSelect = $this.next('div.select-styled');
		    $styledSelect.text($this.children('option').eq(0).text());
		  
		    var $list = $('<ul />', {
		        'class': 'select-options'
		    }).insertAfter($styledSelect);
		  
		    for (var i = 0; i < numberOfOptions; i++) {
		        $('<li />', {
		            text: $this.children('option').eq(i).text(),
		            rel: $this.children('option').eq(i).val()
		        }).appendTo($list);
		    }
		  
		    var $listItems = $list.children('li');
		  
		    $styledSelect.click(function(e) {
		        e.stopPropagation();
		        $('div.select-styled.active').not(this).each(function(){
		            $(this).removeClass('active').next('ul.select-options').hide();
		        });
		        $(this).toggleClass('active').next('ul.select-options').toggle();
		    });
		  
		    $listItems.click(function(e) {
		        e.stopPropagation();
		        $styledSelect.text($(this).text()).removeClass('active');
		        $this.val($(this).attr('rel'));
		        $list.hide();
		        //console.log($this.val());
		    });
		  
		    $(document).click(function() {
		        $styledSelect.removeClass('active');
		        $list.hide();
		    });
		});
	}
	// ***********************
	// custame checked input 
	// **********************
	if ( $('#agree').length > 0) {
		// whene load page if checked input 
		if($('#agree').is(":checked")){
			$('#btn-sign').prop("disabled", false);
		}else{
			$('#btn-sign').prop('disabled','disabled');
		}
		// whene click on checked input 
		$('#agree').click(function(){
			if($(this).is(":checked")){
				$('#btn-sign').prop("disabled", false);
			}else{
				$('#btn-sign').prop('disabled','disabled');
			}
		});
	}

	// uplload image profile
	//----------- file upload -----------------
	if($("#file").length > 0){
		document.getElementById("file").onchange = function() {
			var file = document.getElementById('file').files[0];
			// console.log(file['name']);
			var reader  = new FileReader();

			reader.onload = function(e)  {
                var image = document.createElement("img");
                image.src = e.target.result;
                  // console.log(image.src)
                  // document.body.appendChild(image);
                document.getElementById("aaa").setAttribute("src",image.src);
            }
            reader.readAsDataURL(file);
        }
    }
});