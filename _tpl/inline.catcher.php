<?php
/*
cancel edit
*/

$page_data = $cms->get_page();

// set default content
if ($auth->user['admin'] && !count((array)$page_data['content']['slides'])) {
	$page_data['content']['slides'] = [
		[
			'name' => 'Test',
			'image' => 'beyonce.jpg',
		], [
			'name' => 'It\'s a trap',
			'image' => 'ackbar.jpg',
		],
	];
}

$content = $page_data['content'];
?>

<div class="container">
	<div class="row">
		<div class="col-4">
			<div class="list-group">
				<?php foreach ($page_data['pages'] as $page): ?>
				<a href="/<?=$page['page_name']; ?>" class="list-group-item"><?=$page['name']; ?></a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="col-8">
			<?php if ($page_data['page']): ?>
			<div>
				<div sl-name="logo" sl-type="upload">
					<?=image($content['logo']); ?>
				</div>

				<h1>
					<div sl-name="heading" sl-type="text">
						<?=$content['heading']; ?>
					</div>
				</h1>

				<div sl-name="copy" sl-type="editor">
					<?=$content['copy']; ?>
				</div>

				<div class="glide">
					<div class="glide__track" data-glide-el="track">
						<ul class="glide__slides">
							<?php foreach ($content['slides'] as $k => $slide): ?>
							<li class="glide__slide" sl-name="slides[<?=$k; ?>]">
								<div>
									<div sl-name="slides[<?=$k;?>].image" sl-type="upload">
										<img src="/uploads/<?=$slide['image'];?>">
									</div>
									<div class="slider__info">
				                        <h5 sl-name="slides[<?=$k; ?>].name" sl-type="text"><?=$slide['name']; ?></h5>
				                    </div>
								</div>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="glide__arrows" data-glide-el="controls">
						<button class="glide__arrow glide__arrow--left" data-glide-dir="<">prev</button>
						<button class="glide__arrow glide__arrow--right" data-glide-dir=">">next</button>
					</div>
				</div>

			</div>
			<?php else : ?>
			<div>
				Select a page from the menu
			</div>
			<?php endif; ?>
		</div>

	</div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.core.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.theme.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/glide.min.js"></script>

<script>
	new Glide('.glide').mount()
</script>

<?php $cms->load_page_editor($page_data); ?>