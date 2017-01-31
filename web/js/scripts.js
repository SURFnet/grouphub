var grouphub = (function ($) {
    'use strict';

    var groupSearchReq;
    var searchReq;
    var loggedInUserId = $('body').data('user-id');

    var searchGroups = function () {
        var $this = $(this);
        var $searchContainer = $('#group_search');
        var $searchResults = $searchContainer.children('ul').first();
        var $sort = $searchContainer.find('input:checked');

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
        var $this = $(this);
        var $searchContainer = $this.closest('.search_container');
        var $searchResults = $searchContainer.next('ul');
        var $searchTab = $searchResults.parent();
        var url = $searchResults.data('url');

        if ($searchTab.attr('id') === 'add_members_tab') {
            var searchType = $searchContainer.find("select[name=search-type]").val();
            if (searchType === 'group') {
                url = url.replace('users/search', 'groups/copyable');
            }
        }

        $searchResults.html('<li class="spinner"><i class="fa fa-spinner fa-spin"></li>');

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

    var incrementGroupMemberCount = function (groupId) {
        var newMemberCount = getGroupMemberCount(groupId) + 1;

        updateGroupMemberCounters(groupId, newMemberCount);
    };

    var decrementGroupMemberCount = function (groupId) {
        var newMemberCount = getGroupMemberCount(groupId) - 1;

        updateGroupMemberCounters(groupId, newMemberCount);
    };

    var getGroupMemberCount = function (groupId) {
        var $groupMemberCounts = $('.group-' + groupId).find('.count');

        return parseInt($groupMemberCounts.first().text(), 10);
    };

    var updateGroupMemberCounters = function (groupId, newCount) {
        var $groupMemberCounts = $('.group-' + groupId).find('.count');

        $groupMemberCounts.each(function () {
            $(this).html(newCount);
        });

        var $editGroupMemberCount = $('.edit_group .count');
        $editGroupMemberCount.text('(' + newCount + ')');
    };

    var updateGroups = function () {
        var $groups = $('#groups');

        $.get($groups.data('url'), {query: $('#searchInput').val()}, function (data) {
            $groups.html(data);

            initScroll('#group_all_groups, #group_search');
        });
    };

    var updateGroupsInGroup = function () {
        var $groups = $('.edit_group #group_in_group_tab ul.member_collection');

        $.get($groups.data('url'), function (data) {
            $groups.html(data);
        });
    };

    var userAddMode = function (groupId, userId) {
        var $member = $('.edit_group #group_members_tab .users_or_groups').find('.user-' + userId);
        var $user = $('.edit_group #add_members_tab .users_or_groups').find('.user-' + userId);
        var $tpl = $('.edit_group .user-add-tpl').clone();

        $member.remove();
        $user.find('.actions').html(function () {
            return $tpl.html().replace(/%25gid%25/g, groupId).replace(/%25uid%25/g, userId);
        });
    };

    var userEditMode = function (groupId, userId, value) {
        var $members = $('.edit_group #group_members_tab .users_or_groups');
        var $member = $members.find('.user-' + userId);
        var $user = $('.edit_group #add_members_tab .users_or_groups').find('.user-' + userId);
        var $tpl = $('.edit_group .user-edit-tpl').clone();

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
        var $editGroup = $('#edit_group');
        var $joinConfirm = $('#join_group');
        var $leaveConfirm = $('#group_leave_confirmation');
        var $groupContainer = $('#groups');

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
            var $this = $(this);
            var $group = $('#' + $this.attr('class'));
            var checked = $this.is(':checked');

            if (checked) {
                $group.removeClass('hidden');
            } else {
                $group.addClass('hidden');
            }

            updatePanelsCookie($group.attr('id'), checked);
        });

        $groupContainer.on('click', '.toggle_extra_group_info', function () {
            var $extraGroupInfo = $(this).parents('li').find('.extra-group-info');
            var $span = $(this).find('span');

            $extraGroupInfo.toggleClass('hidden');

            $span.toggleClass('fa-angle-double-down fa-angle-double-up');

            return false;
        });

        $groupContainer.on('click', '.sort .close', function () {
            var $this = $(this);
            var $container = $this.closest('.group');

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
            var $this = $(this);
            var $container = $this.closest('.spinner');

            $container.html('<i class="fa fa-spinner fa-spin">');

            $.get($this.attr('href'), function (data) {
                $container.replaceWith(data);
            });

            return false;
        });

        $groupContainer.on('click', '.group .owned i', function () {
            $(this).toggleClass('fa-angle-down').toggleClass('fa-angle-right');
            var $list = $(this).closest('.owned').next('ul');
            var $header = $(this).closest('.owned');

            $list.toggleClass('hidden');

            $.cookie($header.attr('id') + '_display', ($list.hasClass('hidden') ? 'collapsed' : 'expanded'), {path: '/'});

            return false;
        });

        $groupContainer.on('click', '.sort_menu', function () {
            $(this).next('div').toggleClass('hidden');
        });

        $groupContainer.on('mouseleave', '.sort_menu + div', function () {
            $(this).toggleClass('hidden');
        });

        $groupContainer.on('change', '.sort', function () {
            var $this = $(this);
            var $container = $this.closest('.group');
            var $sort = $this.find('input:checked');
            var isSearch = $container.is('#group_search');
            var query = isSearch ? $('#searchInput').val() : '';

            $.cookie($container.attr('id') + '_sort_order', $sort.val(), {path: '/'});

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
            var $this = $(this);
            var $form = $this.closest('form');

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

        function toggleActiveTab ($tab) {
            var $tabContent = $('#' + $tab.attr('id') + '_tab');
            $tabContent.siblings().addClass('hidden');
            $tab.closest('ul').find('a').removeClass('active');

            $tabContent.removeClass('hidden');
            $tab.addClass('active');

            initScroll($tabContent.attr('id') + ' ul');

            return false;
        }

        $editGroup.on('click', '.tabs a', function() {
            return toggleActiveTab($(this));
        });

        $editGroup.on('click', '.add', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id');
                var $member = $this.closest('li');
                var userId = $member.data('user-id');
                var groupId = $member.data('group-id');

                if (groupId) {
                    var $searchResults = $editGroup.find('#add_groups_tab .users_or_groups');

                    // Init each user
                    $searchResults.find('li').each(function (index, userLi) {
                        var userId = $(userLi).data('user-id');
                        incrementGroupMemberCount(id);
                        userEditMode(id, userId);
                    });

                    updateGroups();
                    updateGroupsInGroup();

                    $member.hide();

                    return;
                }

                incrementGroupMemberCount(id);

                userEditMode(id, userId);

                if (userId == loggedInUserId) {
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

                if (user == loggedInUserId) {
                    updateGroups();
                }
            });
        });

        $editGroup.on('click', '.delete', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id'),
                    $user = $this.closest('li'),
                    $memberGroup = $this.closest('li');


                if ($memberGroup) {
                    $memberGroup.remove();
                    return;
                }

                decrementGroupMemberCount(id);

                userAddMode(id, $user.data('user-id'));

                if ($user.data('user-id') == loggedInUserId) {
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
            var $this = $(this);
            var $form = $this.closest('form');

            $.post($form.attr('action'), $form.serialize(), function () {
                var id = $editGroup.find('.edit_group').data('id');
                var $group = $('.group-' + id);
                var title = $form.find('input').val();
                var descr = $form.find('textarea').val();

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
            var $this = $(this);
            var $form = $this.closest('form');

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
        $editGroup.on('change', 'select[name=search-type]', function() {
            $(this).parent().find('.searchInput').trigger('keyup');
        });

        $editGroup.on('click', '.prospect_details', function () {
            $(this).next('div').find('div.details').toggleClass('hidden');

            return false;
        });

        $editGroup.on('click', '.prospect .confirm, .prospect .cancel', function () {
            var $this = $(this);

            $.post($this.data('url'), function () {
                var id = $editGroup.find('.edit_group').data('id');
                var user = $this.closest('li').data('user-id');

                if ($this.hasClass('confirm')) {

                    incrementGroupMemberCount(id);

                    if (user == loggedInUserId) {
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
            var $this = $(this);
            var $article = $this.closest('article');

            $.post($this.data('url'), function () {
                removeNotification($article.data('id'));

                if (!$this.hasClass('confirm')) {
                    return;
                }

                incrementGroupMemberCount($article.data('group-id'));

                if ($article.data('from-id') == loggedInUserId) {
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
