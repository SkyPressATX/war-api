<?php

namespace War_Api;

class War_Menu {
    /**
     * Get menu for location.
     *
     * @since 1.2.0
     * @param  $request
     * @return array The menu for the corresponding location
     */
    public function war_get_menu() {

        $avail_menus = get_nav_menu_locations();
        $header_menu_slug = $avail_menus["header"];
        $wp_menu = wp_get_nav_menu_object( $header_menu_slug );

        if( empty($wp_menu) ) return true;
        $menu_items = wp_get_nav_menu_items( $wp_menu->term_id );


        if(empty($menu_items)) return true;

        /**
         * wp_get_nav_menu_items() outputs a list that's already sequenced correctly.
         * So the easiest thing to do is to reverse the list and then build our tree
         * from the ground up
         */
        $rev_items = array_reverse ( $menu_items );
        $rev_menu = array();
        $cache = array();
        foreach ( $rev_items as $item ) :
            $formatted = array(
                'ID'          => abs( $item->ID ),
                'order'       => (int) $item->menu_order,
                'parent'      => abs( $item->menu_item_parent ),
                'title'       => $item->title,
                'url'         => $item->url,
                'children'    => array(),
            );
            // Pickup my children
            if ( array_key_exists ( $item->ID , $cache ) ) {
                $formatted['children'] = array_reverse ( $cache[ $item->ID ] );
            }

            $formatted = apply_filters( 'rest_menus_format_menu_item', $formatted );

            if ( $item->menu_item_parent != 0 ) {
                // Wait for parent to pick me up
                if ( array_key_exists ( $item->menu_item_parent , $cache ) ) {
                    array_push( $cache[ $item->menu_item_parent ], $formatted );
                } else {
                    $cache[ $item->menu_item_parent ] = array( $formatted, );
                }
            } else {
                array_push( $rev_menu, $formatted );
            }
        endforeach;
        // return $rev_menu;
        $result = (count($rev_menu) > 1) ? array_reverse($rev_menu) : $rev_menu;
        return $result;
    }

    /**
     * Format a menu item for REST API consumption.
     *
     * @since  1.2.0
     * @param  object|array $menu_item  The menu item
     * @param  bool         $children   Get menu item children (default false)
     * @param  array        $menu       The menu the item belongs to (used when $children is set to true)
     * @return array   a formatted menu item for REST
     */
    private function format_menu_item( $menu_item, $children = false, $menu = array() ) {

        $item = (array) $menu_item;

        $menu_item = array(
            'id'          => abs( $item['ID'] ),
            'order'       => (int) $item['menu_order'],
            'parent'      => abs( $item['menu_item_parent'] ),
            'title'       => $item['title'],
            'url'         => $item['url'],
        );

        if ( $children === true && ! empty( $menu ) ) {
            $menu_item['children'] = $this->get_nav_menu_item_children( $item['ID'], $menu );
        }

        return apply_filters( 'rest_menus_format_menu_item', $menu_item );
    }

    /**
     * Returns all child nav_menu_items under a specific parent.
     *
     * @since   1.2.0
     * @param int   $parent_id      The parent nav_menu_item ID
     * @param array $nav_menu_items Navigation menu items
     * @param bool  $depth          Gives all children or direct children only
     * @return  array   returns filtered array of nav_menu_items
     */
    private function get_nav_menu_item_children( $parent_id, $nav_menu_items, $depth = true ) {

        $nav_menu_item_list = array();

        foreach ( (array) $nav_menu_items as $nav_menu_item ) :

            if ( $nav_menu_item->menu_item_parent == $parent_id ) :

                $nav_menu_item_list[] = $this->format_menu_item( $nav_menu_item, true, $nav_menu_items );

                if ( $depth ) {
                    if ( $children = $this->get_nav_menu_item_children( $nav_menu_item->ID, $nav_menu_items ) ) {
                        $nav_menu_item_list = array_merge( $nav_menu_item_list, $children );
                    }
                }

            endif;

        endforeach;

        return $nav_menu_item_list;
    }
}
