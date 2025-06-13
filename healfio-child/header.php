<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=<?php bloginfo('charset'); ?>">
    <meta id="siteViewport" name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>

    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/custom.css">
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/mediaquery.css">
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<nav id="pr-nav" class="primary-menu navbar navbar-expand-lg navbar-dark2">
    <div class="container-fluid primary-menu-inner px-0">
        <div class="top-wrap">
            <?php if (function_exists('the_custom_logo')) {
                if (has_custom_logo()) {
                    the_custom_logo();
                } else {
                    echo '<a class="custom-logo-link" href="' . esc_url(home_url()) . '"><h5 class="m-0 text-dark">' . get_bloginfo('name') . '</h5></a>';
                }
            } else {
                echo '<a class="custom-logo-link" href="' . esc_url(home_url()) . '"><h5 class="m-0 text-dark">' . get_bloginfo('name') . '</h5></a>';
            } ?>
            <button id="mobile-toggle" class="navbar-toggler animate-button collapsed" type="button"
                    data-toggle="collapse" data-target="#navbarColor01"
                    aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
                <span id="m-tgl-icon" class="animated-icon1"><span></span><span></span></span>
            </button>
        </div>
        <div class="collapse navbar-collapse justify-content-end" id="navbarColor01">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_id' => 'primary-menu',
                'depth' => 3,
                'container' => false,
                'menu_class' => 'navbar-nav pl-3 pr-3',
                'fallback_cb' => 'WP_Bootstrap_Navwalker::fallback',
                'walker' => new WP_Bootstrap_Navwalker()
            ));

            $h_addr_sw = get_theme_mod('h_address_switcher', false);
            $h_call_sw = get_theme_mod('h_call_switcher', false);

            if ($h_addr_sw || $h_call_sw) {
                ?>
                <div class="header-info">
                    <?php if ($h_addr_sw) {
                        $h_addr_link = get_theme_mod('h_address_link');
                        $h_addr_txt = get_theme_mod('h_address_text');

                        ?>
                        <div class="header-address">
                            <a href="<?php if ('' == $h_addr_link) {
                                echo esc_html__('https://goo.gl/maps/XyANinc4EoxHZguc9', 'healfio');
                            } else {
                                echo esc_html($h_addr_link);
                            } ?>" target="_blank">
                                <?php get_template_part('template-parts/header-address-icon'); ?>
                                <p><?php if ('' == $h_addr_txt) {
                                        echo esc_html__('202 Helga Springs Rd, Crawford, TN 38554', 'healfio');
                                    } else {
                                        echo esc_html($h_addr_txt);
                                    } ?></p>
                            </a>
                        </div>
                    <?php } ?>
                    <?php if ($h_call_sw) {

                        $call_num = get_theme_mod('h_call_number');
                        $call_num_txt = get_theme_mod('h_call_number_txt');
                        $call_txt = get_theme_mod('h_call_txt');
                        ?>
                        <div class="header-phone">
                            <a href="<?php if ('' == $call_num) {
                                echo esc_html__('tel:800.275.8777', 'healfio');
                            } else {
                                echo esc_html($call_num);
                            } ?>">
                                <p class="font-weight-600"><?php if ('' == $call_num_txt) {
                                        echo esc_html__('800.275.8777', 'healfio');
                                    } else {
                                        echo esc_html($call_num_txt);
                                    } ?></p>
                                <p class="h-call-us"><?php if ('' == $call_txt) {
                                        echo esc_html__('Call Us', 'healfio');
                                    } else {
                                        echo esc_html($call_txt);
                                    } ?></p>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { echo '<div class="header-info-empty"></div>';} ?>
            				
				<div class="header-icons">
          <label>QUICK TOUR</label>
					<div class="header-screen">
						<a class="menu-item" href="#">
						<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
						 width="" height="19px" viewBox="0 0 1280.000000 972.000000"
						 preserveAspectRatio="xMidYMid meet">
						<metadata>
						Created by potrace 1.15, written by Peter Selinger 2001-2017
						</metadata>
						<g transform="translate(0.000000,972.000000) scale(0.100000,-0.100000)"
						fill="#e1d77b" stroke="none">
						<path d="M0 5455 l0 -4265 1944 0 1944 0 -53 -122 c-129 -299 -198 -603 -220
						-960 l-7 -108 2688 0 2687 0 -6 138 c-15 317 -92 647 -221 941 -25 57 -46 105
						-46 107 0 2 920 4 2045 4 l2045 0 0 4265 0 4265 -6400 0 -6400 0 0 -4265z
						m12270 0 l0 -3735 -5870 0 -5870 0 0 3735 0 3735 5870 0 5870 0 0 -3735z"/>
						<path d="M910 5455 l0 -3355 5490 0 5490 0 0 3355 0 3355 -5490 0 -5490 0 0
						-3355z"/>
						</g>
						</svg>
						</a>
					</div>
					<?php
					set_query_var('header_search', true);
					get_search_form();
					set_query_var('header_search', false);
					?>
					<div class="header-call">
						<a class="menu-item " href="<?php bloginfo('url'); ?>/contact-us"><svg width="" height="22px" viewBox="0 0 28 28" version="1.1" 
							 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
						
						<title>ic_fluent_phone_28_regular</title>
						<desc>Created with Sketch.</desc>
						<g id="🔍-Product-Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
							<g id="ic_fluent_phone_28_regular" fill="#FFFFFFB8" fill-rule="nonzero">
								<path d="M7.92072596,2.64472005 L9.58060919,2.14438844 C11.1435675,1.67327249 12.8134781,2.43464008 13.4828053,3.92352899 L14.5160823,6.22200834 C15.0865915,7.491081 14.7859439,8.98254111 13.7683291,9.93148073 L11.9633958,11.6146057 C11.9410906,11.6354021 11.9227774,11.6601187 11.9093651,11.6875342 C11.720585,12.0734103 12.0066606,13.1043886 12.9061418,14.6623357 C13.9199541,16.4183102 14.7035571,17.1131712 15.0654726,17.005305 L17.4348517,16.2809111 C18.765101,15.8742119 20.2066891,16.3591908 21.0206203,17.4872349 L22.4880851,19.5210248 C23.440761,20.8413581 23.2694403,22.6628821 22.0872853,23.782427 L20.8252038,24.9776653 C19.9337363,25.8219337 18.6854328,26.1763171 17.4833291,25.9264007 C13.966189,25.1951903 10.8150019,22.3628582 8.00336279,17.4929565 C5.18895293,12.6182556 4.31270988,8.46966127 5.44310245,5.05625686 C5.82703045,3.89692852 6.75144427,2.9971717 7.92072596,2.64472005 Z M8.35362603,4.08089467 C7.65205693,4.29236569 7.09740832,4.83222008 6.86705161,5.52781682 C5.89305385,8.46896164 6.6820141,12.2043134 9.30240089,16.7429565 C11.9202871,21.2772684 14.7578382,23.8276999 17.7886493,24.4578029 C18.5099109,24.6077526 19.2588899,24.3951235 19.7937719,23.888561 L21.0558584,22.6933179 C21.6924034,22.0904861 21.784653,21.1096654 21.2716737,20.3987168 L19.8042088,18.3649269 C19.3659382,17.7575185 18.5896985,17.496376 17.8734103,17.7153679 L15.4990066,18.4412788 C14.1848357,18.833027 12.9496858,17.7377562 11.6071037,15.4123357 C10.4705242,13.4437223 10.075962,12.0217729 10.5619671,11.0283539 C10.6558865,10.8363778 10.7841758,10.6632305 10.9404443,10.5175321 L12.7453325,8.83444937 C13.2932789,8.32348189 13.455166,7.52038798 13.1479688,6.83704116 L12.1146918,4.53856182 C11.7542848,3.7368524 10.8551022,3.32688524 10.0135093,3.58056306 L8.35362603,4.08089467 Z" id="🎨-Color">

					</path>
							</g>
						</g>
					</svg>
							</a>
					</div>
					<div class="header-profile dropdown">
						<a class="menu-item " href="<?php bloginfo('url'); ?>/my-account/">
						    <svg width="" height="22px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFFB8"><path d="M16 7.992C16 3.58 12.416 0 8 0S0 3.58 0 7.992c0 2.43 1.104 4.62 2.832 6.09.016.016.032.016.032.032.144.112.288.224.448.336.08.048.144.111.224.175A7.98 7.98 0 0 0 8.016 16a7.98 7.98 0 0 0 4.48-1.375c.08-.048.144-.111.224-.16.144-.111.304-.223.448-.335.016-.016.032-.016.032-.032 1.696-1.487 2.8-3.676 2.8-6.106zm-8 7.001c-1.504 0-2.88-.48-4.016-1.279.016-.128.048-.255.08-.383a4.17 4.17 0 0 1 .416-.991c.176-.304.384-.576.64-.816.24-.24.528-.463.816-.639.304-.176.624-.304.976-.4A4.15 4.15 0 0 1 8 10.342a4.185 4.185 0 0 1 2.928 1.166c.368.368.656.8.864 1.295.112.288.192.592.24.911A7.03 7.03 0 0 1 8 14.993zm-2.448-7.4a2.49 2.49 0 0 1-.208-1.024c0-.351.064-.703.208-1.023.144-.32.336-.607.576-.847.24-.24.528-.431.848-.575.32-.144.672-.208 1.024-.208.368 0 .704.064 1.024.208.32.144.608.336.848.575.24.24.432.528.576.847.144.32.208.672.208 1.023 0 .368-.064.704-.208 1.023a2.84 2.84 0 0 1-.576.848 2.84 2.84 0 0 1-.848.575 2.715 2.715 0 0 1-2.064 0 2.84 2.84 0 0 1-.848-.575 2.526 2.526 0 0 1-.56-.848zm7.424 5.306c0-.032-.016-.048-.016-.08a5.22 5.22 0 0 0-.688-1.406 4.883 4.883 0 0 0-1.088-1.135 5.207 5.207 0 0 0-1.04-.608 2.82 2.82 0 0 0 .464-.383 4.2 4.2 0 0 0 .624-.784 3.624 3.624 0 0 0 .528-1.934 3.71 3.71 0 0 0-.288-1.47 3.799 3.799 0 0 0-.816-1.199 3.845 3.845 0 0 0-1.2-.8 3.72 3.72 0 0 0-1.472-.287 3.72 3.72 0 0 0-1.472.288 3.631 3.631 0 0 0-1.2.815 3.84 3.84 0 0 0-.8 1.199 3.71 3.71 0 0 0-.288 1.47c0 .352.048.688.144 1.007.096.336.224.64.4.927.16.288.384.544.624.784.144.144.304.271.48.383a5.12 5.12 0 0 0-1.04.624c-.416.32-.784.703-1.088 1.119a4.999 4.999 0 0 0-.688 1.406c-.016.032-.016.064-.016.08C1.776 11.636.992 9.91.992 7.992.992 4.14 4.144.991 8 .991s7.008 3.149 7.008 7.001a6.96 6.96 0 0 1-2.032 4.907z"/></svg>
						</a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php bloginfo('url'); ?>/my-account/">My Profile</a></li>
                            <li><a href="<?php bloginfo('url'); ?>/my-account/orders/">My Order</a></li>
                            <!-- <li>
                                <a href="/ecommerce-test-api/my-account/view-order/">My Order Status</a>
                                <?php //
                                    //$hreff = esc_url( home_url( '/my-account/view-order/' . $order->id ) );
                                    //$button_html = '<a href="' . $hreff . '" class="button check-status thankyou-button">' . esc_html__( 'Check Status', 'woocommerce' ) . '</a>';
                                    //echo $button_html;
                                ?>
                            </li> -->
                            <li><a href="<?php bloginfo('url'); ?>/my-account/customer-logout/">Logout</a></li>
                        </ul>
					</div>
					<div class="header-cart-icon"><?php woo_cart_but(); ?></div>
					<div>
						<?php echo do_shortcode('[gtranslate]'); ?>
					</div>
				</div>
			<?php

            $h_cta_btn_switcher = get_theme_mod('h_cta_btn_switcher', false);

            if ($h_cta_btn_switcher) {

                $h_cta_btn_link = get_theme_mod('h_cta_btn_link');
                $h_cta_btn_txt = get_theme_mod('h_cta_btn_txt');

                ?><div class="header-cta"><a href="<?php if ('' == $h_cta_btn_link) {
                    echo esc_html__('/contact-us', 'healfio');
                } else {
                    echo esc_html($h_cta_btn_link);
                } ?>" target="_blank"><div class="d-inline-block elementor-button-link elementor-button elementor-size-md"><?php
                if ('' == $h_cta_btn_txt) {
                    echo esc_html__('Buy Now', 'healfio');
                } else {
                    echo esc_html($h_cta_btn_txt);
                } ?></div></a></div><?php

            }
            ?>
        </div>

    </div>
</nav>
<div id="productMenu" class="megaMenu productMenu">
  <div class="btnWrapper">
    <i class="bi bi-chevron-left backMenu"></i>
    <i class="bi bi-x closeMenu"></i>
  </div>

  <!-- <div class="colGroup">
    <?php //echo do_shortcode('[product_category_filter]'); ?> 
  </div> -->
  <form id="product-category-filter-form" class="productCategoryFilterForm" method="get">
    <div class="colGroup">
      <div class="col">
        <h5>Flower</h5>
        <div class="labelGroup">
          <label for="newdriedFlower"><input class="inputCheck" type="checkbox" id="newdriedFlower" value="dried-flower">Dried Flower</label>
          <label for="newpreRolls"><input class="inputCheck" type="checkbox" id="newpreRolls" value="pre-rolls">Pre-Rolls</label>
          <label for="newshopAllFlower"><input class="inputCheck inputCheckAll" type="checkbox" id="newshopAllFlower" value="Flow">Shop All Flower</label>
        </div>
        <div class="labelGroup">
          <h5>Vapes</h5>
          <label for="disposableVapes"><input class="inputCheck" type="checkbox" id="disposableVapes" value="disposable-Vapes">Disposable Vapes</label>
          <label for="510ThreadCartridg"><input class="inputCheck" type="checkbox" id="510ThreadCartridg" value="510-Thread-Cartridg">510 Thread Cartridges</label>
          <label for="paxPods"><input class="inputCheck" type="checkbox" id="paxPods" value="pax-pods">Pax Pods</label>
          <label for="closedLoopPods"><input class="inputCheck" type="checkbox" id="closedLoopPods" value="closed-loop-pods">Closed Loop Pods</label>
          <label for="shopAllVap"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllVap" value="Vap">Shop All Vapes</label>
        </div>
      </div>

      <div class="col">
        <h5>Concentrates</h5>
        <div class="labelGroup">
          <label for="infusedFlower"><input class="inputCheck" type="checkbox" id="infusedFlower" value="Infused-Pre-Rolls-Flower">Infused Pre-Rolls & Flower</label>
          <label for="hash"><input class="inputCheck" type="checkbox" id="hash" value="Hash">Hash</label>
          <label for="shatter"><input class="inputCheck" type="checkbox" id="shatter" value="Shatter">Shatter</label>
          <label for="rosin"><input class="inputCheck" type="checkbox" id="rosin" value="Rosin">Rosin</label>
          <label for="wax"><input class="inputCheck" type="checkbox" id="wax" value="Wax">Wax</label>
          <label for="kief"><input class="inputCheck" type="checkbox" id="kief" value="Kief">Kief</label>
          <label for="resin"><input class="inputCheck" type="checkbox" id="resin" value="Resin">Resin</label>
          <label for="shopAllConcentrates"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllConcentrates" value="Concentrates">Shop All Concentrates</label>
        </div>
      </div>

      <div class="col">
        <h5>Edibles</h5>
        <div class="labelGroup">
          <label for="Edibleschocalates"><input class="inputCheck" type="checkbox" id="Edibleschocalates" value="Chocalates">Chocalates</label>
          <label for="EdiblesbackedGoods"><input class="inputCheck" type="checkbox" id="EdiblesbackedGoods" value="Baked-Goods">Baked Goods</label>
          <label for="Ediblesgummies"><input class="inputCheck" type="checkbox" id="Ediblesgummies" value="Gummies">Gummies</label>
          <label for="EdibleshardEdibles"><input class="inputCheck" type="checkbox" id="EdibleshardEdibles" value="Hard-Edibles">Hard Edibles</label>
          <label for="Ediblespantry"><input class="inputCheck" type="checkbox" id="Ediblespantry" value="Pantry">Pantry</label>
          <label for="EdiblessavarySnacks"><input class="inputCheck" type="checkbox" id="EdiblessavarySnacks" value="Savary-Snacks">Savary Snacks</label>
          <label for="EdiblesshopAllEdibles"><input class="inputCheck inputCheckAll" type="checkbox" id="EdiblesshopAllEdibles" value="Edibles">Shop All Edibles</label>
        </div>
      </div>

      <div class="col">
        <h5>Extracts</h5>
        <div class="labelGroup">
          <label for="extraoils"><input class="inputCheck" type="checkbox" id="extraoils" value="oil">Oils</label>
          <label for="extracapsules"><input class="inputCheck" type="checkbox" id="extracapsules" value="Capsules">Capsules</label>
          <label for="extrasublingualStrips"><input class="inputCheck" type="checkbox" id="extrasublingualStrips" value="Sublingual-Strips">Sublingual Strips</label>
          <label for="extrashopAllExtracts"><input class="inputCheck inputCheckAll" type="checkbox" id="extrashopAllExtracts" value="Extracts">Shop All Extracts</label>
        </div>
      </div>

      <div class="col">
        <h5>Beverages</h5>
        <div class="labelGroup">
          <label for="coffeesTeas"><input class="inputCheck" type="checkbox" id="coffeesTeas" value="Coffees-Teas">Coffees & Teas</label>
          <label for="softDrinks"><input class="inputCheck" type="checkbox" id="softDrinks" value="Soft-Drinks">Soft Drinks</label>
          <label for="sparklingWaters"><input class="inputCheck" type="checkbox" id="sparklingWaters" value="Sparkling-Waters">Sparkling Waters</label>
          <label for="beverageMixers"><input class="inputCheck" type="checkbox" id="beverageMixers" value="Beverage-Mixers">Beverage Mixers</label>
          <label for="juices"><input class="inputCheck" type="checkbox" id="juices" value="Juices">Juices</label>
          <label for="dealcoholized"><input class="inputCheck" type="checkbox" id="dealcoholized" value="Dealcoholized">Dealcoholized</label>
          <label for="shopAllBeverages"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllBeverages" value="Beverages">Shop All Beverages</label>
        </div>
      </div>

      <div class="col">
        <h5>Topicals</h5>
        <div class="labelGroup">
          <label for="creams"><input class="inputCheck" type="checkbox" id="creams" value="Creams-Lotions">Creams & Lotions</label>
          <label for="bathShower"><input class="inputCheck" type="checkbox" id="bathShower" value="Bath-Shower">Bath & Shower</label>
          <label for="intimacyOils"><input class="inputCheck" type="checkbox" id="intimacyOils" value="Intimacy-Oils">Intimacy Oils</label>
          <label for="transdermalGels"><input class="inputCheck" type="checkbox" id="transdermalGels" value="Transdermal-Gels">Transdermal Gels</label>
          <label for="shopAllTopicals"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllTopicals" value="Topicals">Shop All Topicals</label>
        </div>
      </div>
    </div>
    <div class="menuFooter">
      <button type="button" id="selectAllProductMenu" class="selectAll">Select All</button>
      <button type="submit" id="sortByBtn" class="sortBtn">Sort By</button>
    </div>
  </form>
</div>

<div id="featuredMenu" class="megaMenu featuredMenu">
  <div class="btnWrapper">
    <i class="bi bi-chevron-left backMenu"></i>
    <i class="bi bi-x closeMenu"></i>
  </div>
  
  <form id="featured-category-filter-form" class="featuredCategoryFilterForm" method="get">
    <div class="colGroup">
      <div class="col">
        <h5>New Releases</h5>
        <div class="labelGroup">
          <label for="driedFlower"><input class="inputCheck" type="checkbox" id="driedFlower" value="Dried Flower">Dried Flower</label>
          <label for="preRolls"><input class="inputCheck" type="checkbox" id="preRolls" value="Pre-Rolls">Pre-Rolls</label>
          <label for="shopAllFlower"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllFlower" value="Shop All Flow">Shop All Flower</label>       
        </div>
      </div>

      <div class="col">
        <h5>Shop by potency</h5>
        <div class="labelGroup">        
          <label for="vapes"><input class="inputCheck" type="checkbox" id="vapes" value="vapes">Disposable Vapes</label>
          <label for="cartridges"><input class="inputCheck" type="checkbox" id="cartridges" value="cartridges">510 Thread Cartridges</label>
          <label for="paxpods"><input class="inputCheck" type="checkbox" id="paxpods" value="paxpods">Pax Pods</label>
          <label for="looppods"><input class="inputCheck" type="checkbox" id="looppods" value="looppods">Closed Loop Pods</label>
          <label for="shopAllVapes"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllVapes" value="Shop All Concentrates">Shop All Vapes</label>
        </div>
      </div>

      <div class="col">
        <h5>Trending</h5>
        <div class="labelGroup">
          <label for="chocalates"><input class="inputCheck" type="checkbox" id="chocalates" value="chocalates">Chocalates</label>
          <label for="bakedGoods"><input class="inputCheck" type="checkbox" id="bakedGoods" value="Baked Goods">Baked Goods</label>
          <label for="gummies"><input class="inputCheck" type="checkbox" id="gummies" value="Gummies">Gummies</label>
          <label for="hardEdibles"><input class="inputCheck" type="checkbox" id="hardEdibles" value="Hard Edibles">Hard Edibles</label>
          <label for="pantry"><input class="inputCheck" type="checkbox" id="pantry" value="Pantry">Pantry</label>
          <label for="savarySnacks"><input class="inputCheck" type="checkbox" id="savarySnacks" value="Savary Snacks">Savary Snacks</label>
          <label for="shopAllEdibles"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllEdibles" value="Shop All Edibles">Shop All Edibles</label>
        </div>
      </div>

      <div class="col">
        <h5>Extracts</h5>
        <div class="labelGroup">
          <label for="oils"><input class="inputCheck" type="checkbox" id="oils" value="Oils">Oils</label>
          <label for="capsules"><input class="inputCheck" type="checkbox" id="capsules" value="Capsules">Capsules</label>
          <label for="sublingualStrips"><input class="inputCheck" type="checkbox" id="sublingualStrips" value="Sublingual Strips">Sublingual Strips</label>
          <label for="shopAllExtracts"><input class="inputCheck inputCheckAll" type="checkbox" id="shopAllExtracts" value="Shop All Extracts">Shop All Extracts</label>
        </div>
      </div>

    </div>

    <div class="menuFooter">
      <button type="button" id="selectAllFeaturedMenu" class="selectAll">Select All</button>
      <button type="submit" id="sortBy" class="sortBtn">Sort By</button>
    </div>
  </form>
</div>
<script  type="text/javascript">
  jQuery(document).on('click', '#menu-item-31065', function() {
    jQuery(this).addClass('active');
    jQuery('#productMenu').toggleClass('open');

    jQuery('#featuredMenu').removeClass('open');
    jQuery('#featuredMenu').removeClass('active');
    jQuery('#menu-item-31064').removeClass('active');
  });

  jQuery(document).on('click', '#menu-item-31064', function() {
    jQuery(this).addClass('active');
    jQuery('#featuredMenu').toggleClass('open');

    jQuery('#productMenu').removeClass('open');
    jQuery('#productMenu').removeClass('active');
    jQuery('#menu-item-31065').removeClass('active');
  });

  jQuery(document).on('click', '.closeMenu, .backMenu', function() {
    jQuery('.megaMenu').removeClass('open');
    jQuery('#menu-item-31064, #menu-item-31065').removeClass('active');
  });

  if (jQuery(window).width() < 1199) {
    jQuery(document).on('click', '.megaMenu h5', function() {
      jQuery(this).next('.labelGroup').slideToggle();
      jQuery(this).toggleClass('open');
    });
  }

  // Select All product menu
  const selectAllProductMenu = document.getElementById("selectAllProductMenu");
  const checkboxes_ = document.querySelectorAll(".productMenu .inputCheck");

  selectAllProductMenu.addEventListener("click", function() {
    const allChecked = Array.from(checkboxes_).every(checkbox => checkbox.checked);

    checkboxes_.forEach(checkbox => {
      checkbox.checked = !allChecked;
    });

    selectAllProductMenu.textContent = allChecked ? "Select All" : "Unselect All";
  });

  // Select All Featured menu
  const selectAllFeaturedMenu = document.getElementById("selectAllFeaturedMenu");
  const checkboxes = document.querySelectorAll(".featuredMenu .inputCheck");

  selectAllFeaturedMenu.addEventListener("click", function() {
    const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

    checkboxes.forEach(checkbox => {
      checkbox.checked = !allChecked;
    });

    selectAllFeaturedMenu.textContent = allChecked ? "Select All" : "Unselect All";
  });

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.selectAll').forEach(button => {
      button.addEventListener('click', () => {
        button.classList.toggle('active');
      });
    });
  });


  // ON shop all category

  document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.labelGroup').forEach(labelGroup => {
    const checkAll = labelGroup.querySelector('.inputCheckAll');
    const checkboxes = labelGroup.querySelectorAll('.inputCheck');

    if (checkAll && checkboxes.length) {
      // When "Check All" is toggled
      checkAll.addEventListener('change', () => {
        checkboxes.forEach(cb => {
          cb.checked = checkAll.checked;
        });
      });

      // If any individual checkbox changes
      checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          const allChecked = Array.from(checkboxes).every(box => box.checked);
          checkAll.checked = allChecked;
        });
      });
    }
  });
});

// Product Category filter Form
const siteBaseURL = "<?php echo esc_url(site_url()); ?>";
const form = document.getElementById('product-category-filter-form');
form.addEventListener('submit', function (e) {
  e.preventDefault();
    // const checked = Array.from(document.querySelectorAll('input[name="categories[]"]:checked'));
    const checked = Array.from(document.querySelectorAll('input:checked'));
    if (checked.length === 0) {
        alert('Please select at least one category.');
        return;
    }

    const slugs = checked.map(cb => cb.value).join('+');

    // Redirect to pretty URL
    window.location.href = `${siteBaseURL}/product-filter/${slugs}/`;
});


// Featured Category filter form
(function () {
  // Only set if not already defined
  window.siteBaseURL = window.siteBaseURL || "<?php echo esc_url(site_url()); ?>";

  const form = document.getElementById('featured-category-filter-form');

  // Only proceed if form exists
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const checked = Array.from(document.querySelectorAll('input:checked'));

    if (checked.length === 0) {
      alert('Please select at least one category.');
      return;
    }

    const slugs = checked.map(cb => cb.value).join('+');

    window.location.href = `${siteBaseURL}/product-filter/${slugs}/`;
  });
})();




</script>

<?php
if (!is_page_template('page-templates/template-full-width-page-without-header-title.php')) { ?>
<header id="main-header">
    <?php get_template_part('template-parts/bg-header'); ?>
    <div class="container inner-header">
        <div class="title-wrap">
            <h1 class="header-title"><?php
                if (healfio_is_product()) {
                    the_title();
                } elseif (healfio_is_shop()) {
                    woocommerce_page_title();
                } elseif (is_singular()) {
                    single_post_title();
                } elseif (is_404()) {
                    esc_html_e('404 NOT FOUND', 'healfio');
                } elseif (is_search()) {
                    esc_html_e('Search', 'healfio');
                } elseif (is_archive() && !have_posts()) {
                    esc_html_e('Nothing Found', 'healfio');
                } elseif (is_archive()) {
                    the_archive_title();
                } elseif (is_tax()) {
                    single_term_title();
                } else {
                    $site_description = get_bloginfo('description', 'display');
                    $site_name = get_bloginfo('name');
                    //for home page
                    if ($site_description && (is_home() || is_front_page())):
                        echo esc_html($site_name);
                        echo '<span class="h-site-description">';
                        echo esc_html($site_description);
                        echo '</span>';
                    endif;
                    // for other post pages
                    if (!(is_home()) && !is_404()):
                        the_title();
                        echo ' | ';
                        echo esc_html($site_name);
                    endif;
                } ?></h1><?php
            if (function_exists('bcn_display')) { ?>
                <div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
                    <?php bcn_display(); ?>
                </div>
                <?php
            } ?>
        </div>
    </div>
    <div id="header-wave"></div>
</header>
<?php
}