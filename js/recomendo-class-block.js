/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';
import { TextControl,SelectControl } from '@wordpress/components';

const current_editing = wp.data.select('core/editor').getCurrentPostType();
var recomendo_block_options;
var testing = '',is_woo = '';
var data = {
    'action': 'recomendo_all_templates'
};
$.post(ajax_object.ajax_url, data, function(data) {
    var datajson = JSON.parse(data);
    testing = datajson;
});
$.post(ajax_object.ajax_url, posttype, function(posttype) {
    var obj = JSON.parse(posttype);

    is_woo = obj.is_woocommerce;
    if(obj.post_type === 'product'){
        template_default = 'content-product'
    }else{
        template_default = 'content-recomendo'
    }
});
if(current_editing === 'post'){
    if(is_woo === 'true'){
        recomendo_block_options = [
            {value: 'personalized', label: 'personalized'},
            {value: 'similar', label: 'similar'},
            {value: 'complementary', label: 'complementary'},
            {value: 'trending', label: 'trending'},
        ];	
    }else{
        recomendo_block_options = [
            {value: 'personalized', label: 'personalized'},
            {value: 'similar', label: 'similar'},
            {value: 'trending', label: 'trending'},
        ];	
    }
    
}
else{
    if(is_woo === 'true'){
        recomendo_block_options = [
            {value: 'personalized', label: 'personalized'},
            {value: 'complementary', label: 'complementary'} ,
            {value: 'trending', label: 'trending'},
        ];
    }else{
        recomendo_block_options = [
            {value: 'personalized', label: 'personalized'},
            {value: 'trending', label: 'trending'},
        ];
    }
    
}

class RecomendoBlockClass extends Component {
    constructor(){
        super(...arguments);

    }
 

    componentDidMount(){
        
            for (var i = 1; i <= attributes.number ; i++){
                var li =  document.createElement("li");
                li.innerHTML = 'Editor Mode View'
                document.getElementById('recomendo-main-editor-container'+props.clientId).appendChild(li);
            }
        
    }

    getInspectorControls(){
        const { attributes } = this.props;

        return (
            <InspectorControls key="recomendo-inspector">
                <TextControl 
                    value ={ attributes.number}
					label = { __( 'Number of reccomendations to show' )}
					onChange = { this.changeNumber}
					type = { 'number'}
					min={ 1}
					step ={1} />
                <SelectControl 
                    value = {attributes.type}
                    label = { __( 'Type of recommendations' )}
                    onChange = {this.changeType}
                    options = {recomendo_block_options}
                />
                <SelectControl 
                	value= { attributes.template}
					label= {__('Template to show, please select the name of a template')}
					onChange= { this.changeTemplate}
					options = {testing}
                />
                   
            </InspectorControls>
        )
    }
    //Function to update number attribute
    changeNumber(number){
    
        attributes.setAttributes({number});
        
        deleteChild();
        for (var i = 1; i <= number ; i++){
            var li =  document.createElement("li");
            li.innerHTML = 'Editor Mode View'
            document.getElementById('recomendo-main-editor-container'+props.clientId).appendChild(li);
        }
    
    }
    //Function to update type attribute
    changeType(type){
        attributes.setAttributes({type});
    }
    //function to update template
    changeTemplate(template){
        attributes.setAttributes({template});	
    }
    deleteChild() { 
        var e = document.getElementById("recomendo-main-editor-container"+props.clientId); 
    
        //e.firstElementChild can be used. 
        var child = e.lastElementChild;  
        while (child) { 
            e.removeChild(child); 
            child = e.lastElementChild; 
        } 
    } 
	render(){
        return(
            <div>
                <div id={'recomendo-main-editor-container'+props.clientId} className ='recomendo-editor-preview'>
                </div>
                { this.getInspectorControls() }
            </div>
        );
    }
   
}
export default RecomendoBlockClass;