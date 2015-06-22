{$sec = 'blocktype.openbadgedisplayer'}
<div id="openbadges{$id}"></div>

<script type="text/javascript">
(function ($) {
    function shorten(str) {
        var n = 40;
        return str.substr(0, n-1) + (str.length > n ? '...' : '');
    }

    function formatDate(date) {
        if ( ! date) {
            return '-';
        }
        if (date.toString().match(/^[0-9]+$/)) {
            var d = new Date(0);
            d.setUTCSeconds(date);
            return d.toLocaleDateString();
        }
        return date;
    }

    function urlElement(url) {
        if ( ! url) {
            return TD('-');
        }
        return TD(null, A(\{href: url, title: url, target: '_blank'}, shorten(url) ));
    }

    function expiredBadge(assertion) {
        if ( ! assertion.expires) {
            return false;
        }

        if (assertion.expires.toString().match(/^[0-9]+$/)) {
            return (assertion.expires * 1000) < Date.now();
        }

        return Date.parse(assertion.expires) < Date.now();
    }

    function buildBadgeContent(data) {
        return MochiKit.DOM.toHTML(
            DIV(null, [
                IMG(\{src:data.badge.image, width:'90px', height:'90px', style:'float:left'}, null),
                DIV(\{class: 'openbadge-details', style: 'margin-left:120px'}, [
                    H3('{str tag=issuerdetails section=$sec}'),
                    TABLE(null,
                        TBODY(null, [
                            TR(null, [TD('{str tag=name section=$sec}:'),         TD(data.badge.issuer.name)]),
                            TR(null, [TD('{str tag=url section=$sec}:'),          urlElement(data.badge.issuer.origin)]),
                            TR(null, [TD('{str tag=organization section=$sec}:'), TD(data.badge.issuer.org || '-')])
                        ])
                    ),
                    H3('{str tag=badgedetails section=$sec}'),
                    TABLE(null,
                        TBODY(null, [
                            TR(null, [TD('{str tag=name section=$sec}:'),     TD(data.badge.name)]),
                            TR(null, [TD('{str tag=desc section=$sec}:'),     TD(data.badge.description)]),
                            TR(null, [TD('{str tag=criteria section=$sec}:'), urlElement(data.badge.criteria)])
                        ])
                    ),
                    H3('{str tag=issuancedetails section=$sec}'),
                    TABLE(null,
                        TBODY(null, [
                            TR(null, [TD('{str tag=evidence section=$sec}:'), urlElement(data.evidence) ]),
                            TR(null, [TD('{str tag=issuedon section=$sec}:'), TD( formatDate(data.issued_on) )]),
                            TR(null, [TD('{str tag=expires section=$sec}:'),  TD( formatDate(data.expires) )])
                        ])
                    )
                ])
            ])
        );
    }

    $(function () {
        var id = "{$badgegroup}".split(':');
        if (!id[0] || !id[1]) {
            return;
        }
        var url = '{$baseurl}displayer/' + id[0] + '/group/' + id[1] + '.json?callback=?';
        $.getJSON(url, function(data) {
            $.each(data.badges, function () {
                if (expiredBadge(this.assertion)) {
                    return;
                }
                var img = $('<img>'); img.attr('src', this.assertion.badge.image);
                img.attr('style', 'cursor:pointer; padding-right:15px;'); img.attr('title', this.assertion.badge.name);
                img.width(90); img.height(90);
                var assertion = this.assertion;
                img.click(function () {
                    showPreview('small', \{html: buildBadgeContent(assertion)});
                    $('#viewpreviewinner').width('480px');
                    $("#viewpreview").removeClass('hidden');
                    $("#viewpreview").width('500px');
                    $("#viewpreview").show();

                    disconnectAll('viewpreviewcontent');
                });
                $("#openbadges{$id}").append(img);
            });
        });
    });
})(jQuery);
</script>
