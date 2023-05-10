
var uploadSlideField = function( $params )
{
    var self = this;

    var fileResponderUrl = $params['fileResponderUrl'];

    this.elementId = $params['elementId'];
    this.element = $("#" + this.elementId);

    this.sentRequest = false;

    this.form = undefined;
    this.frame = undefined;

    this.init = function()
	{
        OW.bind('slideshow.upload_file', function( $param ) {
            if ( $param.input_id === self.elementId )
            {
                self.sendFile();
            }
        });

        OW.bind('slideshow.upload_file_complete', function( $param ) {
            if ( $param.input_id === self.elementId )
            {
                self.sendComplete($param);
            }
        });
    }
    
    this.startUpload = function()
    {
        OW.trigger('slideshow.upload_file', [{input_id: self.elementId}])
    }

    this.sendFile = function()
	{
        if ( !this.sentRequest )
        {
            this.sentRequest = true;

            var input = $("#" + this.elementId);

            self.form = $('#' + self.elementId + '_form');

            if ( self.form && self.form.length == 0 )
            {
                self.form = $("<form id='" + self.elementId + "_form' method='POST' action='" + fileResponderUrl + "' enctype='multipart/form-data' target='"+ self.elementId + "_frame'></form>");
                self.form.hide();
            }

            self.frame = $('#' + self.elementId + '_frame');

            if ( self.frame && self.frame.length == 0 )
            {
                self.frame = $("<iframe id='" + self.elementId + "_frame' name='" + self.elementId + "_frame' src='' style='width: 100px; height: 100px;'></iframe>");
                self.frame.hide();
            }

            var cloneInput = input.clone();
            cloneInput.val('');
            cloneInput.attr('disabled', 'disabled');

            OW.trigger('slideshow.upload_file', [{input_id: self.elementId}]); 
            
            $('.' + self.elementId + '_cont').empty().html(cloneInput);

            input.attr('name', 'slide');
            input.appendTo(self.form);

            input.attr('id', self.elementId +'_input');

            $('body').append(self.frame);
            $('body').append(self.form);

            self.form.submit();
        }
    }

    this.sendComplete = function( $param )
	{
        var input = $("#" + this.elementId);

        if ( self.form && self.form.length > 0 )
        {
            self.form.remove();
            self.form = undefined;
        }

        if ( self.frame && self.frame && self.frame.length > 0 )
        {
            self.frame.remove();
            self.frame = undefined;
        }

        input.removeAttr('disabled');
        
        this.sentRequest = false;

        if ( !$param.error )
        {
        	var form_name = input.closest("form").find("input[name=form_name]").val();
        	var main_form = window.owForms[form_name];
        	main_form.elements.slideId.setValue($param.slide_id);
        	main_form.submitForm();
        }
        else
        {
            OW.error($param.message);
            OW.activateNode(input.closest("form").find("input[type=button]"));
        }
    }
}