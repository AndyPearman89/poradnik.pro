<?php
/**
 * Template part: single-pytanie.php
 * Q&A single question view
 * Ref: docs/specs/QA-UI-v1.md
 */

$post_id      = get_the_ID();
$ai_summary   = get_post_meta( $post_id, '_ai_summary', true );
$answer_count = (int) get_post_meta( $post_id, '_answer_count', true );
$category     = get_the_terms( $post_id, 'kategoria' );
$cat_label    = ! empty( $category ) && ! is_wp_error( $category ) ? esc_html( $category[0]->name ) : '';
?>
<section class="container section">
    <article class="qa-single" itemscope itemtype="https://schema.org/QAPage">
        <!-- HERO: pytanie -->
        <header class="qa-hero">
            <?php if ( $cat_label ) : ?>
                <nav class="breadcrumb" aria-label="breadcrumb">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Strona główna', 'generatepress-child-poradnik' ); ?></a>
                    <span aria-hidden="true"> &rsaquo; </span>
                    <span><?php echo $cat_label; ?></span>
                </nav>
            <?php endif; ?>
            <?php the_title( '<h1 class="qa-title" itemprop="name">', '</h1>' ); ?>
            <div class="qa-meta">
                <?php if ( $answer_count ) : ?>
                    <span><?php printf( esc_html( _n( '%d odpowiedź', '%d odpowiedzi', $answer_count, 'generatepress-child-poradnik' ) ), $answer_count ); ?></span>
                    <span aria-hidden="true">&middot;</span>
                <?php endif; ?>
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                <?php if ( $cat_label ) : ?>
                    <span aria-hidden="true">&middot;</span>
                    <span><?php echo $cat_label; ?></span>
                <?php endif; ?>
            </div>
        </header>

        <!-- AI SUMMARY box -->
        <?php if ( $ai_summary ) : ?>
            <div class="ai-summary" role="complementary" aria-label="<?php esc_attr_e( 'Najkrótsza odpowiedź (AI)', 'generatepress-child-poradnik' ); ?>">
                <div class="ai-summary-header">
                    <span class="badge badge-ai">AI</span>
                    <?php esc_html_e( 'Najkrótsza odpowiedź', 'generatepress-child-poradnik' ); ?>
                </div>
                <p itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                    <span itemprop="text"><?php echo wp_kses_post( $ai_summary ); ?></span>
                </p>
            </div>
        <?php endif; ?>

        <!-- Main content / answers -->
        <div class="qa-content" itemprop="suggestedAnswer" itemscope itemtype="https://schema.org/Answer">
            <?php the_content(); ?>
        </div>

        <!-- CTA -->
        <div class="qa-cta">
            <?php get_template_part( 'template-parts/lead/lead', 'form' ); ?>
        </div>
    </article>
</section>
