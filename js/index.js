const {registerBlockType} = wp.blocks; //Blocks API
const {createElement} = wp.element; //React.createElement
const {__} = wp.i18n; //translation functions
const {InspectorControls} = wp.editor; //Block inspector wrapper
const {TextControl,SelectControl,ServerSideRender} = wp.components; //Block inspector wrapper
const svgIcon = createElement('svg', {width:20, height: 20}, createElement('path',{d:"M16.226369889080523,4.541542618471398 L9.157181665301323,0.4000000049650808 L2.104395270347595,4.541542618471398 v8.299784995615482 l7.069188223779202,4.158242382109165 l1.771397513151169,-1.0353856533765793 l3.198356620967388,1.0687851905822754 l-1.148128017783165,-2.2878682985901833 l3.2311602786183355,-1.903773620724678 V4.541542618471398 zM9.107976178824902,10.169364637631192 l-1.771397513151169,-1.0353856533765793 l1.771397513151169,-1.0520854219794273 V10.169364637631192 zM9.206387151777744,8.081893562275186 l1.771397513151169,1.0520854219794273 l-1.771397513151169,1.0353856533765793 V8.081893562275186 zM11.043391980230808,6.946309297281518 v2.0874710753560066 l-1.771397513151169,-1.0520854219794273 L11.043391980230808,6.946309297281518 zM9.255592638254166,5.727226189273608 L11.043391980230808,4.6751407672941845 v2.0874710753560066 L9.255592638254166,5.727226189273608 zM11.043391980230808,9.200778058666005 v2.0874710753560066 l-1.771397513151169,-1.0520854219794273 L11.043391980230808,9.200778058666005 zM12.962405952811242,7.898196107643855 l-1.771397513151169,-1.0353856533765793 l1.771397513151169,-1.0353856533765793 V7.898196107643855 zM11.141802953183651,6.946309297281518 l1.771397513151169,1.0353856533765793 l-1.771397513151169,1.0520854219794273 V6.946309297281518 zM11.141802953183651,9.200778058666005 L12.798387664556504,10.18606440623404 l0.11481280177831656,0.0667990744113923 l-1.771397513151169,1.0353856533765793 V9.200778058666005 zM12.962405952811242,10.336362323659673 v2.0874710753560066 l-1.771397513151169,-1.0353856533765793 L12.962405952811242,10.336362323659673 zM11.141802953183651,4.6751407672941845 l1.771397513151169,1.0520854219794273 l-1.771397513151169,1.0520854219794273 V4.6751407672941845 zM9.206387151777744,5.627027577656522 V3.5395565023005147 l1.771397513151169,1.0520854219794273 L9.206387151777744,5.627027577656522 zM9.107976178824902,5.627027577656522 l-1.771397513151169,-1.0353856533765793 l1.771397513151169,-1.0520854219794273 V5.627027577656522 zM7.287373179197312,4.6751407672941845 l1.771397513151169,1.0520854219794273 l-1.771397513151169,1.0520854219794273 V4.6751407672941845 zM7.188962206244469,6.762611842650189 l-1.771397513151169,-1.0520854219794273 L7.188962206244469,4.6751407672941845 V6.762611842650189 zM7.188962206244469,9.200778058666005 v2.0874710753560066 l-1.771397513151169,-1.0353856533765793 L7.188962206244469,9.200778058666005 zM5.351957377791404,8.081893562275186 l1.771397513151169,1.0520854219794273 l-1.771397513151169,1.0353856533765793 V8.081893562275186 zM7.188962206244469,9.033780372637525 l-1.771397513151169,-1.0520854219794273 l1.771397513151169,-1.0353856533765793 V9.033780372637525 zM5.351957377791404,7.898196107643855 V5.8107250322878485 l1.771397513151169,1.0353856533765793 L5.351957377791404,7.898196107643855 zM5.351957377791404,12.423833399015681 V10.336362323659673 l1.771397513151169,1.0520854219794273 L5.466770179569721,12.357034324604287 L5.351957377791404,12.423833399015681 z"}
				));

	var testing = '',template_default,is_woo = '',woo_columns='';

	/*
	*Ajax calls to php functions 
	*these functions are on recomendo-plugin.php
	*/
	var data = {
		'action': 'recomendo_all_templates'
	};
	var posttype = {
		'action':'return_posttype_variable'
	};    

	$.post(ajax_object.ajax_url, data, function(data) {
		var datajson = JSON.parse(data);
		testing = datajson;
	});
	$.post(ajax_object.ajax_url, posttype, function(posttype) {
		var obj = JSON.parse(posttype);

		is_woo = obj.is_woocommerce;
		
		if( obj.woo_columns != null){
			woo_columns = obj.woo_columns;
		}
		
		if(obj.post_type === 'product'){
			template_default = 'content-product'
		}else{
			template_default = 'content-recomendo'
		}
	});
	/*
	*End of ajax calls
	*/

	



   registerBlockType( 'recomendo/recomendo-block', {
	title: __( 'Recomendo Block' ), // Block title.
    category:  __( 'common' ), //Block category
    icon: svgIcon,
	attributes:  {
		number : {
			default: 12,
		},
		type: {
			default: 'personalized'
		},
		template: {
			default : template_default
		}
	},
	
	edit: function(props){
		const attributes =  props.attributes;
		const setAttributes =  props.setAttributes;
		const current_editing = wp.data.select('core/editor').getCurrentPostType();
		
		var reccomendations_options;
		
		

			if(current_editing === 'post'){
				if(is_woo === 'true'){
					reccomendations_options = [
						{value: 'personalized', label: 'personalized'},
						{value: 'similar', label: 'similar'},
						{value: 'complementary', label: 'complementary'},
						{value: 'trending', label: 'trending'},
					];	
				}else{
					reccomendations_options = [
						{value: 'personalized', label: 'personalized'},
						{value: 'similar', label: 'similar'},
						{value: 'trending', label: 'trending'},
					];	
				}
				
			}
			else{
				if(is_woo === 'true'){
					reccomendations_options = [
						{value: 'personalized', label: 'personalized'},
						{value: 'complementary', label: 'complementary'} ,
						{value: 'trending', label: 'trending'},
					];
				}else{
					reccomendations_options = [
						{value: 'personalized', label: 'personalized'},
						{value: 'trending', label: 'trending'},
					];
				}
				
			}


		$(window).on('load', function(){
			for (var i = 1; i <= attributes.number ; i++){
				var li =  document.createElement("li");
				li.innerHTML = 'Editor Mode View'
				document.getElementById('recomendo-main-editor-container'+props.clientId).appendChild(li);
			}
			
			setColumns();
			// Step 1: Create a new MutationObserver object
			var observer = new MutationObserver( function(mutations) {
				//console.log(mutations); 
				var newblockId= mutations[0].addedNodes[0]['id']; 
				//console.log(newblockId);
				var dataType = document.getElementById(newblockId).getAttribute('data-type');
				if(dataType == 'recomendo/recomendo-block'){
				//console.log('es bloque de recomendo ' + newblockId);
				var justId =  newblockId.substr(6);
				//console.log(justId);
				var isnew = document.getElementById('recomendo-main-editor-container'+justId).childNodes.length;
				if( isnew == 0){
					for (var i = 1; i <= 12 ; i++){
						var li =  document.createElement("li");
						li.innerHTML = 'Editor Mode View'
						document.getElementById('recomendo-main-editor-container'+justId).appendChild(li);
					}
				}
				}
			});

		// Step 2: Observe a DOM node with the observer as callback
		observer.observe(document.querySelector(".editor-block-list__layout"), { attributes: true, childList: true, attributeOldValue: true })

		
						
		});	
		
		
		//Function to update number attribute
		function changeNumber(number){
			
			setAttributes({number});
			
			deleteChild();
			for (var i = 1; i <= number ; i++){
				var li =  document.createElement("li");
				li.innerHTML = 'Editor Mode View'
				document.getElementById('recomendo-main-editor-container'+props.clientId).appendChild(li);
			}
		  
		}
		//Function to update type attribute
		function changeType(type){
			setAttributes({type});
		}
		//function to update template
		function changeTemplate(template){
			setAttributes({template});
			eraseColumns();
			
			setTimeout(function() {setColumns() } , 1000);
			
		}
		function deleteChild() { 
			var e = document.getElementById("recomendo-main-editor-container"+props.clientId); 
			
			//e.firstElementChild can be used. 
			var child = e.lastElementChild;  
			while (child) { 
				e.removeChild(child); 
				child = e.lastElementChild; 
			} 
		} 
		function setColumns(){
			
			switch(woo_columns){
					case 1 : 
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-1');
						break;
					case 2:
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-2');
						break;
					case 3 :
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-3');
						break;
					case 4 :
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-4');
						break;
					case 5 :
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-5');
						break;
					case 6 :
						document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-6');
						break;
					default :	
					document.getElementById('recomendo-main-editor-container'+props.clientId).classList.add('columns-3');			
				}
			
		}

		function eraseColumns(){
		var element =document.getElementById('recomendo-main-editor-container'+props.clientId).classList;
		
		for (var i=0; i < element.length; ++i) {
			if(/columns-.*/.test(element[i])) {
				var classes = element[i];
				element.remove(classes);
				break;
			}
			
		}

		}
		
		//Display block preview and UI
		return createElement('div', {
				id: 'recomendo-main-editor-container'+props.clientId,
				className : 'recomendo-editor-preview',
			}, [
		
		//Block inspector
		createElement( InspectorControls, {},
			[
				//A simple text control for number
				createElement(TextControl, {
					value: attributes.number,
					label: __( 'Number of reccomendations to show' ),
					onChange: changeNumber,
					type: 'number',
					min: 1,
					step: 1
				}),
				//Select type of reccomendations
				createElement(SelectControl, {
					value: attributes.type,
					label: __( 'Type of recommendations' ),
					onChange: changeType,
					options: reccomendations_options
				}),
				//Select showing templates to use
				createElement(SelectControl,{
					value: attributes.template,
					label:__('Template to show, please select the name of a template'),
					onChange: changeTemplate,
					options:testing
				})
			]
		)
	] )
	
		
	},
	save(props){
		const attributes =  props.attributes;
		createElement('div',{}, [
			createElement(ServerSideRender,{
				attributes : attributes,
				block: 'recomendo/recomendo-block'
			})
		])
	}
});