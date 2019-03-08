<?php
//require_once('_lib/blog.php');
$blog = new blog(
    array('blog_index'=>0)
);
?>

<div class="container">
    <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
		<?php
		foreach( $blog->content as $k=>$v ){
			$comments=sql_query("SELECT * FROM comments WHERE blog='".escape($v['id'])."'");
	
			$article_categories=sql_query("SELECT * FROM cms_multiple_select CMS
				INNER JOIN blog_categories C ON C.id=CMS.value
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
						<?php
						if( !$blog->article ){
	                    ?>
						<a href="/blog/<?=$v['page name'] ?: $v['id'];?>">Continue reading...</a>
						<?php
						}
						?>
					</p>
				</div>
				<!--/article-->
	
			<?php
			if( $blog->article ){
				$author=$cms->get('users',$v['user'],1);
			?>
			<div class="clear"></div>
			<p class="posted">
				<?php if($author['name']){ ?>
				By:<a href="#"><?=$author['name'];?> <?=$author['surname'];?></a> |
				<?php } ?>
	
				<?php if( count($article_categories) ){ ?>
				Posted in:
				<?php foreach( $article_categories as $k=>$c ){ ?>
					<a href="/blog/category/<?=$c['page_name'];?>"><?=$c['category'];?></a>
					<?php if( $k<count($article_categories)-1 ){ ?>
					,
					<?php } ?>
				<?php } ?>
				<?php } ?>
			</p>
			<p>&nbsp;</p>
	        <div class="clear"></div>
			<?php
			}
			?>
	
	    </div>
		<!--/article-holder-->
	
	    </div>
		<!--/blog-info-->
	
	
		<?php
		if( $blog->article ){
		?>
	
	    <div class="commentsHolder">
	    <h2 class="comments">Comments</h2>
	
		<?php
		if( count($comments) ){
		?>
		<form method="post">
			<a name="comments"></a>
		<?php
		}
	
		foreach( $comments as $k=>$v ){
			if( $v['approved'] or $auth->user['admin'] or 1==1 ){
		?>
			<div style="border: 1px solid #cccccc; padding:10px; margin:10px 0;"> <a name="comment-<?=$v['id'];?>"></a>
				<?=nl2br($v['comment']);?><br />
				<em><strong>
				<?php if( $v['website'] ){ ?>
				    <a href="<?=$v['website'];?>" rel="nofollow"><?=$v['name'];?></a>
				<?php }else{ ?>
				    <?=$v['name'];?>
				<?php } ?>
				</strong>
	            on <?=dateformat('d/m/Y',$v['date']);?>
				</em>
				</div>
				<?php
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
				<label>Name:</label><br>
				<input type="text" name="name" id="name" value="" class="field"/>
			</p>
			<div class="clear"></div>
			<p>
				<label>Email:<span class="small">(will not be published)</span></label><br>
				<input type="text" name="email" id="email" value="" class="field"/>
			</p>
			<div class="clear"></div>
			<p>
				<label>Comment:</label><br>
				<textarea name="comment" id="comment" cols="60" rows="4" class="autogrow"></textarea>
			</p>
			<div class="clear"></div>
			<div class="blog-submit-btn">
				<button type="submit">Send</button>
			</div>
		</form>
		<script type="text/javascript">
		document.getElementById('nospam').value=1;
		</script>
		<!--contact-form-->
	
		</div>
		<!--/commentsHolder-->
	
			<?php
			}
		}
		?>
	</div>
	
	<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
		<h2>Categories</h2>
		<ul class="categories">
		<?php
		foreach( $blog->categories as $k=>$v ){
		?>
			<li><a href="/blog//category/<?=$v['page_name'];?>"><?=$v['category'];?></a></li>
		<?php
		}
		?>
		</ul>
		<div class="sidebar-divider"></div>
		<!--/sidebar-divider-->

		<h2>Archive</h2>

		<?php
		$archive_opts=array();
		foreach( $blog->months as $k=>$v ){
			$archive_opts[dateformat('Y/m',$v['date'])] = dateformat('F Y',$v['date']);
		}
		?>

        <ul>
            <?php foreach( $archive_opts as $k=>$v ){ ?>
            <li><a href="/blog/<?=$k;?>"><?=$v;?></a></li>
            <?php } ?>
        </ul>

		<p></p>
		<div class="sidebar-divider"> </div>
		<!--/sidebar-divider-->
	</div>
</div>

