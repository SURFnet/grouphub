# Templating

Since are somewhat deeply nested and most are named *group* sometimes it's
hard to discover what means what. The tree below represents the include structure
for all lists of groups.

    base.html.twig
        group_categories.html.twig
            my_groups.html.twig
                _sort.html.twig
                my_groups-groups.html.twig (my-owner)
                    _memberships.html.twig 
                my_groups-groups.html.twig (my-admin)
                    _memberships.html.twig 
                my_groups-groups.html.twig (my-member)
                    _memberships.html.twig 
            organisation_groups.html.twig
                _sort.html.twig
                organisation_groups-groups.html.twig (org-owner)
                    _memberships.html.twig
                organisation_groups-groups.html.twig (org-admin)
                    _memberships.html.twig
                organisation_groups-groups.html.twig (org-member)
                    _memberships.html.twig
            all_groups.html.twig
                _sort.html.twig
                all_groups-groups.html.twig 
                    _groups.html.twig
    
    search.html.twig
        _sort.html.twig [organisationGroups.sort]
        search-results.html.twig 
            
