<?php
/**
 * The template for displaying Single page.
 */
get_header();
get_template_part('/template-parts/site', 'breadcrumb');
?>
<section id="abiz-blog-section" class="blog-section st-py-default">
	<div class="container">
		<div class="row gy-lg-0 gy-5 wow fadeInUp">
			<div class="<?php if ( is_active_sidebar('sidebar-primary') ){ esc_attr_e('col-lg-8','abiz'); } else { esc_attr_e('col-lg-12','abiz'); } ?>">
				<div class="row row-cols-1 gy-5">
					<?php if( have_posts() ): ?>
						<?php while( have_posts() ): the_post(); ?>
							<div class="col">
								<?php get_template_part('template-parts/content','page');	?>
							</div>
						<?php endwhile; ?>
					<?php endif; ?>
					<?php get_template_part('template-parts/author','details'); // Show Author Details ?>
					<?php comments_template( '', true ); // show comments  ?>
				</div>
			</div>
			<?php get_sidebar(); // Sidebar ?>
		</div>
	</div>
</section>
<?php get_footer(); ?>
