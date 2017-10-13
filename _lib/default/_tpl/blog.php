<?
//require_once('_lib/blog.php');
$blog = new blog(
    array('blog_index'=>0)
);
?>

<div id="blogIntro">
	<h1>Blog</h1>
	<h2>Subheading</h2>
</div>

<div id="blog-content">
	<div id="sidebar">
		<h2>Categories</h2>
		<ul class="categories">
		<?
		foreach( $blog->categories as $k=>$v ){
		?>
			<li><a href="/category/<?=$v['page_name'];?>"><?=$v['category'];?></a></li>
		<?
		}
		?>
		</ul>
		<div class="sidebar-divider"></div>
		<!--/sidebar-divider-->

		<h2>Archive</h2>

		<?
		$archive_opts=array();
		foreach( $blog->months as $k=>$v ){
			$archive_opts[dateformat('Y/m',$v['date'])]=dateformat('F Y',$v['date']);
		}
		?>

        <? /*
		<select id="archive" name="archive" onchange="location.href='/blog/'+this.options[this.selectedIndex].value">
			<option value=""></option>
			<?=html_options($archive_opts, $sections[1].'/'.$sections[2]); ?>
		</select>
        */ ?>

        <ul>
            <? foreach( $archive_opts as $k=>$v ){ ?>
            <li><a href="/<?=$k;?>"><?=$v;?></a></li>
            <? } ?>
        </ul>

		<p></p>
		<div class="sidebar-divider"> </div>
		<!--/sidebar-divider-->
	</div>
	<!--/sidebar-->

	<?

	foreach( $blog->content as $k=>$v ){
		$comments=sql_query("SELECT * FROM comments WHERE blog='".escape($v['id'])."'");

		$article_categories=sql_query("SELECT * FROM cms_multiple_select CMS
			INNER JOIN categories C ON C.id=CMS.value
			WHERE
				item='".escape($v['id'])."' AND
				section='blog'
			GROUP BY category
		");

		if( !$blog->article ){
			$v['copy']=truncate(strip_tags($v['copy']),200);
		}
	?>

    <div class="blog-info">
		<div>
			<div class="date">
				<p style="color:#98c93c;"><?=dateformat('F dS, Y',$v['date']);?></p>
			</div>
			<!--/date-->
		</div>
		<!--/div-->

		<div class="clear"></div>

		<div id="articleHolder">
			<div class="article">
				<h1><?=$v['heading'];?></h1>
                <p>
					<?=$v['copy'];?>
					<?
					if( !$blog->article ){
                    ?>
					<a href="/<?=$v['page name'] ?: $v['id'];?>">Continue reading...</a>
					<?
					}
					?>
				</p>
			</div>
			<!--/article-->

		<?
		if( $blog->article ){
			$author=$cms->get('users',$v['user'],1);
		?>
		<div class="clear"></div>
		<p class="posted">
			<? if($author['name']){ ?>
			By:<a href="#"><?=$author['name'];?> <?=$author['surname'];?></a> |
			<? } ?>

			<? if( count($article_categories) ){ ?>
			Posted in:
			<? foreach( $article_categories as $k=>$c ){ ?>
				<a href="/blog/category/<?=$c['page_name'];?>"><?=$c['category'];?></a>
				<? if( $k<count($article_categories)-1 ){ ?>
				,
				<? } ?>
			<? } ?>
			<? } ?>
		</p>
		<p>&nbsp;</p>
        <div class="clear"></div>
		<?
		}
		?>

    </div>
	<!--/article-holder-->

    </div>
	<!--/blog-info-->


	<?
	if( $blog->article ){
	?>

    <div class="commentsHolder">
    <h2 class="comments">Comments</h2>

	<?
	if( count($comments) ){
	?>
	<form method="post">
		<a name="comments"></a>
	<?
	}

	foreach( $comments as $k=>$v ){
		if( $v['approved'] or $auth->user['admin'] or 1==1 ){
	?>
		<div style="border: 1px solid #cccccc; padding:10px; margin:10px 0;"> <a name="comment-<?=$v['id'];?>"></a>
			<?=nl2br($v['comment']);?><br />
			<em><strong>
			<? if( $v['website'] ){ ?>
			    <a href="<?=$v['website'];?>" rel="nofollow"><?=$v['name'];?></a>
			<? }else{ ?>
			    <?=$v['name'];?>
			<? } ?>
			</strong>
            on <?=dateformat('d/m/Y',$v['date']);?>
			</em>
			</div>
			<?
			}
	}
	?>
	</form>
	<br>

	<form method="post" id="blog-form" class="validate" errorMethod="alert">
		<input type="hidden" name="save_blog_comment" value="1">
		<input type="hidden" id="nospam" name="nospam" value="0" />
		<input type="hidden" id="nospam" name="blog" value="<?=$blog->content[0]['id'];?>" />
		<p>
			<label>Name:<br /></label>
			<input type="text" name="name" id="name" value="" class="field"/>
		</p>
		<div class="clear"></div>
		<p>
			<label>Email <span class="small">(will not be published)</span><br /></label>
			<input type="text" name="email" id="email" value="" class="field"/>
		</p>
		<div class="clear"></div>
		<p>
			<label>Comment<br /></label>
			<textarea name="comment" id="comment" cols="60" rows="4" class="autogrow"></textarea>
		</p>
		<div class="clear"></div>
		<div class="blog-submit-btn">
				<input type="image" src="/images/send-button.jpg" value="Send" border="0">
		</div>
	</form>
	<script type="text/javascript">
	document.getElementById('nospam').value=1;
	</script>
	<!--contact-form-->

	</div>
	<!--/commentsHolder-->

	<?
	}
}
?>

	</div>
	<!--/articleHolder-->

	<div class="clearer">&nbsp;</div>

</div>
<!--/blog-content-->
