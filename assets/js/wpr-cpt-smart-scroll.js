jQuery(document).ready(function ($) {
    var slideEl = $('#wpr-display-posts');
    if (slideEl.length) {
        var slider = $(slideEl).attr('data-post-slide');
        var ppp = $(slideEl).attr('data-post-number');
        var page = 2;
        var button = $('.wpr-display-posts .wpr-load-more');
        var slideIndex = $('.wpr-post-slide').length;
        var slideNr = $(slideEl).attr('data-index');
        var loading = false;
        var scrollHandling = {
            allow: true,
            reallow: function () {
                scrollHandling.allow = true;
            },
            delay: 400
        };

        wpr_add_div_element();

        var last_scroll = 0;
        $(window).scroll(function () {
            if (!loading && scrollHandling.allow) {
                slideIndex = $('.wpr-post-slide').length;
                scrollHandling.allow = false;
                setTimeout(scrollHandling.reallow, scrollHandling.delay);
                var offset = $(button).offset().top - $(window).scrollTop();
                if (2000 > offset && slideIndex <= slideNr) {
                    loading = true;
                    var arrayOfPosts = $.map($(".wpr-post-slide"), function (n, i) {
                        return n.id.match(/\d+$/);
                    });

                    var data = {
                        action: 'more_post_slides_ajax',
                        nonce: ajax_object.nonce,
                        page: page,
                        slider: slider,
                        query: ajax_object.query,
                        ppp: ppp,
                        slidenr: slideNr,
                        slideindex: slideIndex,
                        currenturl: ajax_object.currenturl,
                        currentittems: arrayOfPosts
                    };
                    $.post(ajax_object.ajax_url, data, function (res) {
                        if (res) {
                            $('.wpr-load-more').before(res);
                            page = page + 1;
                            loading = false;
                            wpr_add_div_element();
                        } else {

                        }
                    }).fail(function (xhr, textStatus, e) {

                    });
                }
            }

            var scroll_pos = $(window).scrollTop();
            if (Math.abs(scroll_pos - last_scroll) > $(window).height() * 0.1) {

                last_scroll = scroll_pos;

                $('#wpr-display-posts .wpr-post-slide').each(function () {

                    var scroll_pos = $(window).scrollTop();
                    var el_top = $(this).offset().top;
                    var el_height = $(this).outerHeight();
                    var el_bottom = el_top - el_height;
                    if (( el_bottom > scroll_pos )) {
                        if (window.location.href !== $(this).attr("data-url")) {
                            history.replaceState(null, null, $(this).attr("data-url"));

                            if (typeof ga === 'function') {
                                ga(
                                    'send',
                                    {
                                        'hitType': 'pageview',
                                        'page': location.pathname,
                                        'title': ajax_object.currenttitle
                                    }
                                );
                            }
                        }
                        return ( false );
                    }

                });

            }
        });
    }
});

function wpr_add_div_element() {
    var myEls = jQuery('#wpr-display-posts > div.wpr-post-slide');
    var numEls = myEls.length;
    var $x = 1;
    for (i = 0; i < numEls; i++) {
        console.log('i: ' + i);
        console.log('$x: ' + $x);
        if ($x % 3 === 0) {
            if (!myEls.eq(i).next().hasClass('content_hint')) {
                myEls.eq(i).after('<div class="content_hint"></div>');
            }

        } else if ((i + 3) > numEls) {
            console.log('added here last');
            if (!myEls.last().next().hasClass('content_hint')) {
                myEls.last().after('<div class="content_hint"></div>');
            }
        }
        $x++;
    }

}