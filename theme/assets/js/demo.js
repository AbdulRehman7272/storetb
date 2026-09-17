( function ( $ ) {
    'use strict';

    // show code
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('pre code').forEach((el) => {
          hljs.highlightElement(el);
        });
    });

    $('.demo-show-code-btn').each(function(){
        $(this).on('click', function(){
            var $this = $(this);

            if($this.hasClass('code-showed')){
                $this.removeClass('code-showed').find('span').text('Show Code')
                if($this.parent().siblings().find('.demo-card-body-content').hasClass('d-flex')){
                    
                    $this.parent().siblings().find('.demo-card-body-content').removeClass('d-none').addClass('d-flex');
                }else{
                    $this.parent().siblings().find('.demo-card-body-content').removeClass('d-none');
                }
                
                $this.parent().siblings().find('.demo-card-body-code').addClass('d-none');

            }else{
              $this.addClass('code-showed').find('span').text('Preview')
              $this.parent().siblings().find('.demo-card-body-content').removeClass('d-flex').addClass('d-none');
              $this.parent().siblings().find('.demo-card-body-code').removeClass('d-none');
            }
        });
    })

    var successIcon =  `<svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.1094 1.38428L6.10938 12.3843L1.10938 7.38428" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;

    var errorIcon = `<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.83203 15.2837C11.698 15.2837 14.832 12.1497 14.832 8.28369C14.832 4.4177 11.698 1.28369 7.83203 1.28369C3.96604 1.28369 0.832031 4.4177 0.832031 8.28369C0.832031 12.1497 3.96604 15.2837 7.83203 15.2837Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.83203 5.48364V8.28364" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.83203 11.0837H7.83903" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;

    // copy by clipboard
    let clipboard = new ClipboardJS(".btn-clipboard", {
        target: function(e) {
            return e.parentNode.nextElementSibling
        },
    });

    clipboard.on('success', function (e) {
        $(e.trigger)
        .attr("title", "Copied!")
        .attr('data-bs-original-title', "Copied!")
        .html(successIcon)
        .addClass('text-success')
        .removeClass('text-white')
        .tooltip("dispose")
        .tooltip("show");

        e.clearSelection();

    }).on('error', function (e) {
        $(e.trigger)
        .attr("title", "Failed!")
        .attr('data-bs-original-title', "Failed!")
        .html(errorIcon)
        .addClass('text-danger')
        .removeClass('text-white')
        .tooltip("dispose")
        .tooltip("show");

        e.clearSelection();
    });

}(jQuery) )

