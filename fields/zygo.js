var ZYGO_NUM_ALL=[];

jQuery(function ($) {

	$("document").ready(function() {
		$('.zygo_head a').click(function(){
			zygoAddNew(this.getAttribute('rel'));
		});
		zygoAddEvents($('.zygo_table'));
		zygoSetOrdering();
	});


	function zygoSetOrdering(){
		$('button, input').click(function(){
			zygoOrdering();
		});
	}
	function zygoOrdering(){
		$('.zygo_wrapper').each(function(i, el){
			var vals = [];
			$(el).find('.jform_params_userinfofieldName').each(function(j, elem){
				if(elem.value != "uniqueID0") vals.push(elem.value);
			});
			$(el).find('.zygo_ordering').val(vals.join("|"));
			console.log(vals.join("|"));
		});		
	}

	function zygoAddEvents(cont){


		cont.find('.zygo_toggle_btn').click(function(){

			var zygo_more = $(this).closest('.zygo_line').find('.zygo_more');
			if(zygo_more.is(":visible")){
				$(this).removeClass('active');
				zygo_more.slideUp();
				$(this).closest('.zygo_line').removeClass('open');
			}else{
				$(this).addClass('active');
				zygo_more.slideDown();
				$(this).closest('.zygo_line').addClass('open');
			}
		});

		cont.find('.zygo_remove_btn').click(function(){
			if($(this).closest('.zygo_line').hasClass("zygo_line0")) return;
			$(this).closest('.zygo_line').remove();
			zygoOrdering();

		});

		cont.find('.zygo_type').change(function(){		

			var cnt = $(this).closest('.zygo_line');

			if(this.value=="select" || this.value=="multiselect" 
				|| this.value=="radio" || this.value=="checkboxes"){

				cnt.find('.zygo_more_param_fieldOptions').show();
				
			}else{
				cnt.find('.zygo_more_param_fieldOptions').hide();
			}

			var fdv = cnt.find('.zygo_more_param_fieldDefaultValue');
			var fpms = cnt.find('.zygo_more_param_fieldParams .zygo_more_label');

			if(this.value=="html"){
				fdv.hide();
				fpms.html(ZYGO_FIELDPARAMS_HTML);
			}else{
				fdv.show();
				fpms.html(ZYGO_FIELDPARAMS);
			}

		});

		cont.find('.zygo-add-option').click(function(){

			var fieldname = $(this).attr('fname');
			var name = $(this).attr('pname');
			var num = $(this).closest('.zygo_line').find('.zygoid_wrapper').text();

			var html = $('<tr class="zygo-option-div"><td><input type="text" name="jform[params]['+
			fieldname+']['+name+'_value]['+num
			+'][]" value="" /></td><td><input type="text" name="jform[params]['+fieldname+
			']['+name+'_text]['+num+'][]" value="" />'+
			'<input type="button" class="zygo-remove-option btn btn-danger" value="-"></td></tr>');
			
			$(this).closest('.zygo_multitext_wrapper').
				find('.zygo-options-list>tbody').append(html);
			zygoRemoveOption(html);
		});					

		zygoRemoveOption(cont);
	}	

	function zygoRemoveOption(cont){

		cont.find('.zygo-remove-option').click(function(){
			$(this).closest('.zygo-option-div').remove();
		});	
	}

	function zygoAddNew(fieldname){

		ZE_NUM=ZYGO_NUM_ALL[fieldname];

		var newTab = $("<tr class='zygo_line zygo_line"+ZE_NUM+"'>");
		
		var inner = $("#zygo_"+fieldname+" .zygo_line0").html();

		inner = inner.replace(/jform\[params\]\[(\w*)\]\[(\w*)\]\[0\]/g, "jform[params][$1][$2]["+ZE_NUM+"]");
		inner = inner.replace(/jform\[params\]\[(\w*)\]\[(\w*)\]\[(\w*)\]\[0\]/g, "jform[params][$1][$2][$3]["+ZE_NUM+"]");
		inner = inner.replace("uniqueID0", "uniqueID"+ZE_NUM);

		newTab.html(inner);
		$("#zygo_table_"+fieldname+">tbody").append(newTab);

		newTab.find(".zygo_code_input").val(ZYGO_ADD_NEW_FIELD+ZE_NUM);
		newTab.find(".zygoid .zygo_tdhead span").html(ZE_NUM);


		ZE_NUM++;
		ZYGO_NUM_ALL[fieldname]=ZE_NUM;

		var sortableList = new jQuery.JSortableList('#zygo_'+fieldname+' .zygo_table>tbody','', '','','','');
		zygoAddEvents(newTab);


		newTab.find(".chzn-container").remove();

		newTab.find("select").show().removeClass("chzn-done").chosen({
				disable_search_threshold : 10,
				allow_single_deselect : true
		});
		zygoSetOrdering();
		zygoOrdering();
	}

});