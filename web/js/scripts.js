var grouphub = (function ($) {
    'use strict';

    var groupSearchReq, searchReq,
        userId = $('body').data('user-id');

    var searchGroups = function () {
        var $this = $(this),
            $searchContainer = $('#group_search'),
            $searchResults = $searchContainer.children('ul').first(),
            $sort = $searchContainer.find('input:checked');

        groupSearchReq = $.get({
            url: $searchResults.data('url'),
            data: {query: $this.val(), sort: $sort.val()},
            beforeSend: function () {
                groupSearchReq && groupSearchReq.abort();
            },
            success: function (data) {
                $searchContainer.removeClass('hidden');
                $searchResults.html(data);
                initScroll('#group_search');
            }
        });
    };

    var searchUsersOrGroups = function () {
        var $this = $(this),
            $searchContainer = $this.closest('.search_container'),
            $searchResults = $searchContainer.next('ul');

        var url = $searchResults.data('url');
        var type = $searchContainer.find("input[name='search-type']:checked").val();
        if (type == 'group') {
            url = url.replace('users', 'groups');
        }

        searchReq = $.get({
            url: url,
            data: {
                query: $this.val()
            },
            beforeSend: function () {
                searchReq && searchReq.abort();
            },
            success: function (data) {
                $searchResults.html(data);
                initScroll($searchResults);
            }
        });
    };

    var initScroll = function (el) {
        var $el = $(el);

        $el.data('jscroll', null);

        $el.jscroll({
            nextSelector: 'a.groups-next',
            loadingHtml: '<li class="spinner"><i class="fa fa-spinner fa-spin"></li>'
        });
    };

    var lowerGroupCount = function (groupId) {
        var $counts = $('.group-' + groupId).find('.count');

        $counts.each(function () {
            var $this = $(this);

            $this.html(parseInt($this.text(), 10) - 1);
        });
    };

    var raiseGroupCount = function (groupId) {
        var $counts = $('.group-' + groupId).find('.count');

        $counts.each(function () {
            var $this = $(this);

            $this.html(parseInt($this.text(), 10) + 1);
        });
    };

    var updateGroups = function () {
        var $groups = $('#groups');

        $.get($groups.data('url'), {query: $('#searchInput').val()}, function (data) {
            $groups.html(data);

            initScroll('#group_all_groups, #group_search');
        });
    };

    var userAddMode = function (groupId, userId) {
        var $member = $('.edit_group #group_members_tab .users').find('.user-' + userId),
            $user = $('.edit_group #add_members_tab .users').find('.user-' + userId),
            $tpl = $('.edit_group .user-add-tpl').clone();

        $member.remove();
        $user.find('.actions').html(function () {
            return $tpl.html().replace(/%25gid%25/g, groupId).replace(/%25uid%25/g, userId);
        });
    };

    var userEditMode = function (groupId, userId, value) {
        var $members = $('.edit_group #group_members_tab .users'),
            $member = $members.find('.user-' + userId),
            $user = $('.edit_group #add_members_tab .users').find('.user-' + userId),
            $tpl = $('.edit_group .user-edit-tpl').clone();

        value = typeof value !== 'undefined' ? value : 'member';

        $tpl.find('select option[value=' + value + ']').attr('selected', 'selected');
        $tpl = $tpl.html().replace(/%25gid%25/g, groupId).replace(/%25uid%25/g, userId);

        $user.find('.actions').html($tpl);

        if ($member.length == 0) {
            $members.append($user.clone());
        } else {
            $member.find('.actions').html($tpl);
        }
    };

    var removeNotification = function (id) {
        var $count = $('#notifications_link').find('span');

        $('#notifications').find('.notification-' + id).remove();

        $count.html(parseInt($count.text(), 10) - 1);
    };

    var refreshNotificationsCount = function () {
        var $countLink = $('#notifications_link');

        $countLink.find('span').load($countLink.data('count-url'));
    };

    var updatePanelsCookie = function (id, visible) {
        var hiddenPanels = $.cookie('panels') || {};

        hiddenPanels[id] = visible;

        $.cookie('panels', hiddenPanels, {path: '/', expires: 7});
    };

    var init = function () {
        var $editGroup = $('#edit_group'),
            $joinConfirm = $('#join_group'),
            $leaveConfirm = $('#group_leave_confirmation'),
            $groupContainer = $('#groups');

        refreshNotificationsCount();

        $('#language_selector_link').on('click', function () {
            $('#language_selector_menu').toggleClass('hidden');

            return false;
        });

        $('#searchInput').on('keyup', $.debounce(250, searchGroups));

        $('#new_group_link').on('click', function () {
            $('body').addClass('modal-open');
            $('#new_group').removeClass('hidden');

            return false;
        });

        $('section').on('click', '.close-modal', function () {
            $('body').removeClass('modal-open');
            $(this).closest('section').addClass('hidden');

            return false;
        });

        $('nav ul li input').on('change', function () {
            var $this = $(this),
                $group = $('#' + $this.attr('class')),
                checked = $this.is(':checked');

            if (checked) {
                $group.removeClass('hidden');
            } else {
                $group.addClass('hidden');
            }

            updatePanelsCookie($group.attr('id'), checked);
        });

        $groupContainer.on('click', '.sort .close', function () {
            var $this = $(this),
                $container = $this.closest('.group');

            $container.addClass('hidden');

            updatePanelsCookie($container.attr('id'), false);

            if ($container.is('#group_search')) {
                $('#searchInput').val('');
            } else {
                $('input.' + $container.attr('id')).removeAttr('checked');
            }

            return false;
        });

        $groupContainer.on('click', '.spinner a', function () {
            var $this = $(this),
                $container = $this.closest('.spinner');

            $container.html('<i class="fa fa-spinner fa-spin">');

            $.get($this.attr('href'), function (data) {
                $container.replaceWith(data);
            });

            return false;
        });

        $groupContainer.on('click', '.group .owned i', function () {
            $(this).toggleClass('fa-angle-down').toggleClass('fa-angle-right');
            $(this).closest('.owned').next('ul').toggleClass('hidden');

            return false;
        });

        $groupContainer.on('click', '#sort_menu_blue, #sort_menu_green, #sort_menu_purple, #sort_menu_grey', function () {
            $(this).next('div').toggleClass('hidden');
        });

        $groupContainer.on('change', '.sort', function () {
            var $this = $(this),
                $container = $this.closest('.group'),
                $sort = $this.find('input:checked'),
                isSearch = $container.is('#group_search'),
                query = isSearch ? $('#searchInput').val() : '';

            $.get($container.data('url'), {query: query, sort: $sort.val()}, function (data) {
                $container.replaceWith(data);
                initScroll('#' + $container.attr('id'));
            });
        });

        $groupContainer.on('click', '.button_join', function () {
            var url =  $(this).data('url');

            if (!url) {
                return false;
            }

            $joinConfirm.find('.group-name').html($(this).data('name'));
            $joinConfirm.find('.confirm').data('url', url);
            $joinConfirm.toggleClass('hidden');

            return false;
        });

        $joinConfirm.on('click', '.confirm', function () {
            var $this = $(this),
                $form = $this.closest('form');

            $form.attr('action', $this.data('url'));
            $form.submit();

            return false;
        });

        $joinConfirm.on('click', '.cancel', function () {
            $joinConfirm.toggleClass('hidden');

            return false;
        });

        $groupContainer.on('click', '.button_member', function () {
            var url =  $(this).data('url');

            if (!url) {
                return false;
            }

            $leaveConfirm.find('.confirm').data('url', url);
            $leaveConfirm.toggleClass('hidden');

            return false;
        });

        $leaveConfirm.on('click', '.confirm', function () {
            var $form = $('<form>', {
                action: $(this).data('url'),
                method: 'post'
            }).appendTo('body');

            $form.submit();

            return false;
        });

        $leaveConfirm.on('click', '.cancel', function () {
            $leaveConfirm.toggleClass('hidden');

            return false;
        });

        $groupContainer.on('click', '.group_section, .button_edit', function () {
            $('body').addClass('modal-open');

            $editGroup.load($(this).data('url'), function () {
                $(this).removeClass('hidden');

                initScroll('#group_members_tab ul');
            });

            return false;
        });

        $editGroup.on('click', '#show_group_details', function() {
            if (!$('#group_title').hasClass('hidden')) {
                $('#group_details').toggleClass('hidden');
            }

            return false;
        });

        $editGroup.on('click', '#add_members', function() {
            $('#group_members').removeClass('active');
            $('#group_members_tab').addClass('hidden');

            $('#add_members').addClass('active');
            $('#add_members_tab').removeClass('hidden');

            initScroll('#add_members_tab ul');

            return false;
        });

        $editGroup.on('click', '#group_members', function() {
            $('#group_members').addClass('active');
            $('#group_members_tab').removeClass('hidden');

            $('#add_members').removeClass('active');
            $('#add_members_tab').addClass('hidden');

            initScroll('#group_members_tab ul');

            return false;
        });

        $editGroup.on('click', '.add', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    user = $this.closest('li').data('user-id');

                raiseGroupCount(id);

                userEditMode(id, user);

                if (user == userId) {
                    updateGroups();
                }
            });

            return false;
        });

        $editGroup.on('change', '.roles', function () {
            var $this = $(this);

            $.post($this.data('url'), {'role': $this.val()}, function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    user = $this.closest('li').data('user-id');

                userEditMode(id, user, $this.val());

                if (user == userId) {
                    updateGroups();
                }
            });
        });

        $editGroup.on('click', '.delete', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    user = $this.closest('li').data('user-id');

                lowerGroupCount(id);

                userAddMode(id, user);

                if (user == userId) {
                    updateGroups();
                }
            });

            return false;
        });

        $editGroup.on('click', '#edit_group_link', function () {
            var $this = $(this);

            $this.closest('li').toggleClass('edit');
            $('#group_details').addClass('hidden');
            $this.closest('.edit_group').find('#group_title, #group_name, #show_group_details, #edit_group_details').toggleClass('hidden');

            return false;
        });

        $editGroup.on("keypress", "header form :input:not(textarea)", function(e) {
            return e.keyCode != 13;
        });

        $editGroup.on('change', 'header form :input', function () {
            var $this = $(this),
                $form = $this.closest('form');

            $.post($form.attr('action'), $form.serialize(), function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    $group = $('.group-' + id),
                    title = $form.find('input').val(),
                    descr = $form.find('textarea').val();

                descr = descr.length === 0 ? '&nbsp;' : descr;

                $('#group_title').html(title);
                $('#group_details').html(descr);

                $group.find('.name').html(title);
                $group.find('.description').html(descr);

                if ($this.is('select')) {
                    updateGroups();
                }
            });
        });

        $editGroup.on('click', '#delete_group_link', function () {
            $('#group_deletion_confirmation').toggleClass('hidden');

            return false;
        });

        $editGroup.on('click', '#group_deletion_confirmation a.confirm', function () {
            var $this = $(this),
                $form = $this.closest('form');

            $form.attr('action', $this.data('url'));
            $form.submit();

            return false;
        });

        $editGroup.on('click', '#group_deletion_confirmation a.cancel', function () {
            $('#group_deletion_confirmation').toggleClass('hidden');

            return false;
        });

        $editGroup.on('keyup', '.searchInput', $.debounce(250, searchUsersOrGroups));

        // Trigger search when type is changed
        $editGroup.on('click', 'input[name=search-type]', function(){
            $('.searchInput').trigger('keyup');
        });

        $editGroup.on('click', '.prospect_details', function () {
            $(this).next('div').find('div.details').toggleClass('hidden');

            return false;
        });

        $editGroup.on('click', '.prospect .confirm, .prospect .cancel', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    user = $this.closest('li').data('user-id');

                if ($this.hasClass('confirm')) {

                    raiseGroupCount(id);

                    if (user == userId) {
                        updateGroups();
                    }

                    userEditMode(id, user);
                } else {
                    userAddMode(id, user);
                }

                refreshNotificationsCount();
            });

            return false;
        });

        $('#notifications_link').on('click', function () {
            $('body').addClass('modal-open');

            $('#notifications').load($(this).data('url'), function () {
                $(this).removeClass('hidden');
            });

            return false;
        });

        $('#notifications').on('click', '.confirm, .cancel', function () {
            var $this = $(this),
                $article = $this.closest('article');

            $.post($this.data('url'), function () {
                removeNotification($article.data('id'));

                if (!$this.hasClass('confirm')) {
                    return;
                }

                raiseGroupCount($article.data('group-id'));

                if ($article.data('from-id') == userId) {
                    updateGroups();
                }
            });

            return false;
        });

        initScroll('#group_all_groups');

        window.setInterval(refreshNotificationsCount, 1000 * 60);
        window.setInterval(updateGroups, 1000 * 60 * 5);
    };

    return {
        init: init
    };

}(window.jQuery));

jQuery().ready(function () {
    'use strict';

    Pace.options.ajax.trackMethods.push('POST');

    $.cookie.json = true;

    grouphub.init();

    $('.tooltip').tooltipster();
});
