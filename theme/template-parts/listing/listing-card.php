<?php
/**
 * Template part: listing-card.php
 * Listing card (archive view)
 * Ref: docs/specs/LISTINGS-UI-v1.md
 */

$post_id    = get_the_ID();
$location   = get_post_meta( $post_id, '_listing_location', true );
$rating     = (float) get_post_meta( $post_id, '_listing_rating', true );
$review_cnt = (int) get_post_meta( $post_id, '_review_count', true );
$plan       = get_post_meta( $post_id, '_listing_plan', true ); // premium_plus|premium|verified|free
$excerpt    = get_the_excerpt();

$badge_map = [
    'premium_plus' => [ 'class' => 'badge-premium-plus', 'label' => 'PREMIUM+' ],
    'premium'      => [ 'class' => 'badge-premium',      'label' => 'PREMIUM'  ],
    'verified'     => [ 'class' => 'badge-verified',     'label' => 'VERIFIED' ],
];
$badge = isset( $badge_map[ $plan ] ) ? $badge_map[ $plan ] : null;
?>
<article class="card listing-card" itemscope itemtype="https://schema.org/LocalBusiness">
    <?php if ( $badge ) : ?>
        <span class="badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
    <?php endif; ?>

    <h3 class="listing-name" itemprop="name"><?php the_title(); ?></h3>

    <?php if ( $location ) : ?>
        <p class="listing-location" itemprop="address"><?php echo esc_html( $location ); ?></p>
    <?php endif; ?>

    <?php if ( $rating > 0 ) : ?>
        <p class="listing-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
            <meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>">
            <meta itemprop="reviewCount" content="<?php echo esc_attr( $review_cnt ); ?>">
            <?php
            $star_count = min( 5, (int) round( $rating ) );
            echo '<span aria-hidden="true">' . str_repeat( '⭐', $star_count ) . '</span>';
            ?>
            <span class="sr-only"><?php printf( esc_html__( 'Ocena %s na 5', 'generatepress-child-poradnik' ), esc_html( number_format( $rating, 1 ) ) ); ?></span>
            <?php echo esc_html( number_format( $rating, 1 ) ); ?>
            <?php if ( $review_cnt ) : ?>
                <span>(<?php echo esc_html( $review_cnt ); ?> <?php esc_html_e( 'opinii', 'generatepress-child-poradnik' ); ?>)</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ( $excerpt ) : ?>
        <p class="listing-desc"><?php echo esc_html( $excerpt ); ?></p>
    <?php endif; ?>

    <div class="listing-actions">
        <a class="btn btn-sm" href="<?php the_permalink(); ?>#lead-form">
            <?php esc_html_e( 'Zapytaj', 'generatepress-child-poradnik' ); ?>
        </a>
        <a class="btn btn-secondary btn-sm" href="<?php the_permalink(); ?>">
            <?php esc_html_e( 'Zobacz profil', 'generatepress-child-poradnik' ); ?>
        </a>
    </div>
</article>