<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="link-performance-report wp-core-ui">
    <div class="stats-range">
        <ul>
            <?php foreach ( $range_nav as $nrange => $label ) : ?>
                <li<?php echo ( $nrange == $current_range ) ? ' class="current"' : ''; ?>>
                    <a href="<?php echo admin_url( 'edit.php?post_type=idatlink&page=idat-reports&range=' . $nrange ); ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="custom-range">
                <span><?php _e( 'Custom' , 'idataffiliates' ); ?></span>
                <form id="custom-date-range" method="GET">
                    <input type="hidden" name="post_type" value="<?php echo $cpt_slug; ?>">
                    <input type="hidden" name="page" value="idat-reports">
                    <input type="hidden" name="range" value="custom">
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" class="range_datepicker from" required>
                    <span>&mdash;</span>
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $end_date ); ?>" name="end_date" class="range_datepicker to" required>
                    <button type="submit" class="button"><?php _e( 'Go' , 'idataffiliates' ); ?></button>
                </form>
            </li>

            <?php do_action( 'ta_stats_reporting_menu_items' , $data ); ?>

        </ul>
    </div>
    <div class="report-chart-wrap">

        <div class="chart-sidebar">

            <ul class="chart-legend">
                <li style="border-color: #3498db">
                    <?php _e( 'General' , 'idataffiliates' ); ?>
                    <span><?php _e( 'All links' , 'idataffiliates' ); ?></span>
                </li>
            </ul>

            <div class="add-legend">
                <label for="add-report-data"><?php _e( 'Fetch report for specific link:' , 'idataffiliates' ); ?></label>
                <div class="input-wrap">
                    <input type="text" id="add-report-data" placeholder="<?php esc_attr_e( 'Search affiliate link' , 'idataffiliates' ); ?>"
                        data-range="<?php echo esc_attr( $current_range ); ?>"
                        data-start-date="<?php echo esc_attr( $start_date ); ?>"
                        data-end-date="<?php echo esc_attr( $end_date ); ?>"
                        data-linkid="<?php echo esc_attr( $link_id ); ?>">
                    <ul class="link-search-result" style="display: none;"></ul>
                </div>

                <div class="input-wrap link-report-color-field" style="display:none;">
                    <input type="text" class="color-field" id="link-report-color" value="#e74c3c">
                </div>

                <button type="button" class="button-primary" id="fetch-link-report"><?php _e( 'Fetch Report' , 'idataffiliates' ); ?></button>
            </div>

            <?php do_action( 'ta_stats_reporting_chart_sidebar' ); ?>

        </div>

        <div class="report-chart-placeholder"></div>

        <?php do_action( 'ta_stats_reporting_after_chart_placeholder' ); ?>

    </div>
    <div class="overlay"></div>
</div>

<script type="text/javascript">
    var report_data = { 'click_counts' :<?php echo json_encode( $data ); ?>},
        report_details = {
            label       : '<?php echo _e( 'All links' , 'idataffiliates' ); ?>',
            timeformat  : '<?php echo ( $range[ 'type' ] == 'year' ) ? '%b' : '%d %b'; ?>',
            minTickSize : [ 1 , "<?php echo ( $range[ 'type' ] == 'year' ) ? 'month' : 'day'; ?>" ],
            clicksLabel : '<?php _e( 'Clicks: ' , 'idataffiliates' ); ?>'
        },
        main_chart;
</script>

<?php do_action( 'ta_after_link_performace_report' , $range , $data ); ?>
