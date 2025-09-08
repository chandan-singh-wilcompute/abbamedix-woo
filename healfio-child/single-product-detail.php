<?php
	/**
	 * Template Name: single Product Detail
	 *
	 * @package WordPress
	 * @subpackage Healfio-child
	 * @since Healfio 1.0
	 */

	get_header(); ?>

  
  

  <style>
    body {
      background-color: #0A1106
    }
  </style>
  
<div class="container">
  
	<div class="productDetails" style="padding: 50px 0">
    <div class="progressWrapper">
      <div class="tchProgress">
        <label>TCH</label>
        <div class="progressContainer">
          <span class="active"></span>
          <span class="active"></span>
          <span class="active"></span>
          <span></span>
        </div>
        28-32%
      </div>

      <div class="cbdProgress">
        <label>CBD</label>
        <div class="progressContainer">
          <span class="active"></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
        0-1%
      </div>
    </div>

    <div class="iconWrapper">
      <?php 
      // Get actual product strain data instead of hardcoded values
      global $product;
      if ($product) {
          $strain = $product->get_attribute('strain');
          if ($strain) : ?>
      <div class="icon">
        <span class="strain <?php echo sanitize_html_class(strtolower($strain)); ?>">
          <?php echo esc_html($strain); ?>
        </span>
        <p><?php echo esc_html($strain); ?></p>
      </div>
          <?php endif; 
      } ?>

      <div class="icon">
        <span class="myRecene"></span>
        <p>Myrecene</p>
      </div>

      <div class="icon">
        <span class="limonene"></span>
        <p>Limonene</p>
      </div>

      <div class="icon">
        <span class="linalool"></span>
        <p>Linalool</p>
      </div>
    </div>

    <div class="devider"></div>

    <div class="flexGroup">
      <div class="productSizeButtons">
        <button type="submit">5g</button>
        <button type="submit">10g</button>
        <button type="submit">15g</button>
      </div>

      <div class="productQuantity">    
        <button type="button" class="btn minusQuntity decrease">−</button>
        <input type="number" class="quantity" value="1" min="1">
        <button type="button" class="btn addQuntity increase">+</button>
      </div>

      <div class="productPrice">
        53.95$ / Price per gram: 10.79$
      </div>
    </div>

    <div class="addToCart">
      <p class="rxReductionValue"><label>RX Reduction Value &nbsp;</label><Strong>1&nbsp;g</Strong></p>
      <button type="submit" class="addToCartBtn">Add to cart</button>
    </div>

 	</div>
</div>
  
   <script>
    const qtyInput = document.querySelector('.productQuantity .quantity');
    const increaseBtn = document.querySelector('.productQuantity .increase');
    const decreaseBtn = document.querySelector('.productQuantity .decrease');

    increaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(qtyInput.value) || 0;
      qtyInput.value = currentValue + 1;
    });

    decreaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(qtyInput.value) || 0;
      const min = parseInt(qtyInput.min) || 0;
      if (currentValue > min) {
        qtyInput.value = currentValue - 1;
      }
    });
  </script>







	<?php
	get_footer();
