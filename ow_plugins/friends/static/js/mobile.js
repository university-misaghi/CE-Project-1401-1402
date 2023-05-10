var OWM_FriendsConsole = function( params )
{
    var self = this;
    self.params = params;

    this.consoleAcceptRequest = function( $node )
    {
        var rid = $node.data("rid");
        var $row = $node.closest(".owm_sidebar_msg_item");
        $.ajax({
            url: self.params.acceptUrl,
            type: "POST",
            data: { "id": rid },
            dataType: "json",
            success : function(data) {
                if ( data ) {
                    $row.remove();
                    OWM.trigger('mobile.console_item_removed', { section : "friend-requests" });
                    if ( data.result == true && data.message != undefined ) {
                        OWM.info(data.message);
                    }
                }
            }
        });
    };

    this.consoleIgnoreRequest = function( $node )
    {
        var rid = $node.data("rid");
        var $row = $node.closest(".owm_sidebar_msg_item");
        $.ajax({
            url: self.params.ignoreUrl,
            type: "POST",
            data: { "id": rid },
            dataType: "json",
            success : function(data) {
                if ( data ) {
                    $row.remove();
                    OWM.trigger('mobile.console_item_removed', { section : "friend-requests" });
                }
            }
        });
    };

    this.consoleLoadMore = function( $node )
    {
        $node.addClass("owm_sidebar_load_more_preloader");

        var exclude =
        $("li.owm_sidebar_msg_item", "#friend-requests-list")
            .map(function(){
                return $(this).data("reqid");
            })
            .get();

        OWM.loadComponent(
            "FRIENDS_MCMP_ConsoleItems",
            { limit: 3, exclude: exclude },
            {
                onReady: function(html){
                    $("#friend-requests-list").append(html);
                    $node.removeClass("owm_sidebar_load_more_preloader");
                }
            }
        );
    };

    this.hideLoadMoreButton = function()
    {
        $("#friends-load-more").closest(".owm_sidebar_msg_list").hide();
    };

    $("body")
        .on("click", "a.owm_friend_request_accept", function(){
            self.consoleAcceptRequest($(this));
        })
        .on("click", "a.owm_friend_request_ignore", function(){
            self.consoleIgnoreRequest($(this));
        })
        .on("click", "a#friends-load-more", function(){
            self.consoleLoadMore($(this));
        });

    OWM.bind("mobile.console_hide_friends_load_more", function(){
        self.hideLoadMoreButton();
    });

    OWM.bind("mobile.console_load_new_items", function(data){
        if ( data.page == 'notifications' && data.section == 'friends' )
        {
            $("#friend-requests-cap").show();
            $("#friend-requests-list").prepend(data.markup);
        }
    });

    OWM.bind("mobile.console_item_removed", function( data ){
        if ( data.section == "friend-requests" )
        {
            if ( $("#friend-requests-list li").length == 0 )
            {
                $("#friend-requests-cap").hide();
            }
        }
    });

    OWM.bind("mobile.hide_sidebar", function(data){
        if ( data.type == "right" )
        {
            OWM.unbind("mobile.console_hide_friends_load_more");
            OWM.unbind("mobile.console_load_new_items");
            $("body")
                .off("click", "a.owm_friend_request_accept")
                .off("click", "a.owm_friend_request_ignore")
                .off("click", "a#friends-load-more");
        }
    });
};