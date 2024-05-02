<?php
$cms->register_block_handler([
	'name' => 'Carousel',
	'render' => function ($data) {
        ?>
        
<div class="glide">
	<div class="glide__track" data-glide-el="track">
		<ul class="glide__slides">

		<?php
		foreach ($data as $v):
		?>
		<li class="glide__slide" sl-name="slides[<?=$k; ?>]">
			<img src="<?=$v['url'];?>">
	        <h3><?=$v['thumbnails'];?></h3>
			<div><?=$v['caption'];?></div>
		</li>
		<?php
		endforeach;
		?>

		</ul>
	</div>

	<div class="glide__arrows" data-glide-el="controls">
		<button class="glide__arrow glide__arrow--left" data-glide-dir="<"><i class="fas fa-chevron-left"></i></button>
		<button class="glide__arrow glide__arrow--right" data-glide-dir=">"><i class="fas fa-chevron-right"></i></button>
	</div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.core.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.theme.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/glide.min.js"></script>

<script>
	window.addEventListener('load', function () {
		if (!document.querySelector('.glide')) {
			return;
		}
		
		var glide = new Glide(".glide",
			{
				type: "carousel",
				gap: 80,
				perView: 3,
				perSwipe: '|',
				breakpoints: {
					800: {
						perView: 1
					}
				}
			}).mount();
	});
</script>

        <?php
	},
	'setup' => function () {
		?>
		<script src="https://cdn.jsdelivr.net/npm/editorjs-carousel@1.0.7/dist/bundle.min.js"></script>
		<?php
	},
	'config' => [
        'endpoints' => [
            'byFile' => '/_lib/api/v2/?cmd=uploads',
        ]
	]
]);