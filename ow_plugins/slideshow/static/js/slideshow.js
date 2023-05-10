var slideshow = function( params )
{
	var self = this;
	
	this.sizes = params.sizes;
	this.pagination = params.pagination == "true";
	this.interval = params.interval;
	this.uniqname = params.uniqname
	this.effect = params.effect;
    this.preloadImage = params.preloadImage;
	
	this.sliderHeight = null;
	
	this.init = function()
	{
		switch ( self.interval )
        {
        	case 'long':
        		var interval = 6000; break;
        	case 'medium':
        		var interval = 4000; break;
        	case 'short':
        		var interval = 2000; break;
        }
		
		var $slideshow = $("#slideshow-" + self.uniqname);
		var sliderWidth = $slideshow.width();
		self.sliderHeight = self.calcHeight(sliderWidth);

		$slideshow.find(".slides_container").css("height", self.sliderHeight);

        $slideshow.slides({
            pagination: self.pagination,
            generatePagination: self.pagination,
            effect: self.effect,
            play: interval,
            preload: true,
            preloadImage: self.preloadImage,
            animationStart: function(current) {
        		var $caption = $("#caption-" + self.uniqname + "-" + current);
        		var $slide = $caption.parent();
        		offset = self.calcOffset($slide.height());
        		$caption.css("display", "block").animate({bottom: Math.abs(offset)-35},100);
            },
            animationComplete: function(current) {
            	var $caption = $("#caption-" + self.uniqname + "-" + current);
        		var $slide = $caption.parent();
        		offset = self.calcOffset($slide.height());
                $caption.animate({bottom:Math.abs(offset)},200);
            },
            slidesLoaded: function(data) {
                $slideshow.find(".ow_slide img").css("width", $slideshow.width()).fadeIn();

                $(".ow_slide", $slideshow).each(function() {
                	var offset = self.calcOffset($(this).height());
                	$(this).css("top", offset);
                });
                
                var $caption = $("#caption-" + self.uniqname + "-1");
        		var $slide = $caption.parent();
        		offset = self.calcOffset($slide.height());
                $caption.css("display", "block").animate({bottom:Math.abs(offset)},200);
            }
        });
	}
	
	this.calcHeight = function( newWidth )
	{
		var heights = new Array();
		
		for ( var size in self.sizes )
		{
			heights.push(self.calcScaledHeight(self.sizes[size]["width"], self.sizes[size]["height"], newWidth));
		}
		
		minHeight = Math.min.apply(null, heights);
		
		return parseInt(minHeight);
	}
	
	this.calcOffset = function( height )
	{
		var offset = 0;
		
		if ( height != self.sliderHeight ) {
            if ( height > self.sliderHeight ) {
                var offset = - (height - self.sliderHeight) / 2;
            }
            else {
                var offset = (self.sliderHeight - height) / 2;
            }
        }
		
		return offset;
	}
	
	this.calcScaledHeight = function( width, height, newWidth )
	{
		return newWidth / width * height;
	}
}