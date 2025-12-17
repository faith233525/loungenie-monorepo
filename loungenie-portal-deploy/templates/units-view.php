<?php
/**
 * Units View Template
 * Displays all units with comprehensive filtering options
 */
?>

<!-- Units Cards View -->
<?php if ( ! empty( $units ) ) : ?>
	<div id="units-cards" class="lgp-card-grid" aria-live="polite">
		<?php foreach ( $units as $unit ) :
			$gateway_info   = $gateway_summary[ (int) $unit->company_id ] ?? null;
			$gateway_status = $gateway_info && ( (int) $gateway_info->gateway_count > 0 ) ? 'online' : 'offline';
			$last_sync      = $gateway_info && $gateway_info->last_sync ? date_i18n( get_option( 'date_format' ), strtotime( $gateway_info->last_sync ) ) : __( 'No sync data', 'loungenie-portal' );
			?>
			<div class="lgp-unit-card"
				data-color="<?php echo esc_attr( $unit->color_tag ?? '' ); ?>"
				data-season="<?php echo esc_attr( $unit->season ?? '' ); ?>"
				data-venue="<?php echo esc_attr( $unit->venue_type ?? '' ); ?>"
				data-lock-brand="<?php echo esc_attr( $unit->lock_brand ?? '' ); ?>"
				data-status="<?php echo esc_attr( $unit->status ); ?>">
				<div class="lgp-unit-card-header">
					<div>
						<h3 class="lgp-card-title" style="margin: 0;">#<?php echo esc_html( $unit->id ); ?> · <?php echo esc_html( $unit->company_name ); ?></h3>
						<p class="lgp-text-muted" style="margin-top: 4px;"><?php echo esc_html( $unit->address ?? __( 'No address on file', 'loungenie-portal' ) ); ?></p>
					</div>
					<div class="lgp-badges">
						<span class="lgp-badge <?php echo esc_attr( strtolower( $unit->status ) === 'active' ? 'active' : 'inactive' ); ?>"><?php echo esc_html( ucfirst( $unit->status ) ); ?></span>
						<span class="lgp-badge <?php echo esc_attr( $gateway_status ); ?>"><?php echo esc_html( $gateway_status === 'online' ? __( 'Gateway Online', 'loungenie-portal' ) : __( 'No Gateway', 'loungenie-portal' ) ); ?></span>
						<?php if ( $unit->color_tag ) : ?>
							<span class="lgp-badge" style="background:#f5f3ff;color:#5b21b6;border-color:#ddd6fe;">
								<?php echo esc_html( $unit->color_tag ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>

				<div class="lgp-unit-meta">
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Lock Brand', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $unit->lock_brand ?? '—' ); ?></span>
					</div>
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Lock Type', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $unit->lock_type ?? '—' ); ?></span>
					</div>
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Venue', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $unit->venue_type ?? '—' ); ?></span>
					</div>
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Season', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $unit->season ?? '—' ); ?></span>
					</div>
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Gateway Count', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $gateway_info->gateway_count ?? 0 ); ?></span>
					</div>
					<div class="lgp-meta-item">
						<span class="lgp-meta-label"><?php esc_html_e( 'Last Sync', 'loungenie-portal' ); ?></span>
						<span><?php echo esc_html( $last_sync ); ?></span>
					</div>
				</div>

				<div class="lgp-card-actions">
					<a class="lgp-btn lgp-btn-secondary" href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>">
						<?php esc_html_e( 'View Details', 'loungenie-portal' ); ?>
					</a>
					<a class="lgp-btn lgp-btn-primary" href="<?php echo esc_url( home_url( '/portal/tickets' ) ); ?>">
						<?php esc_html_e( 'Service Request', 'loungenie-portal' ); ?>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<p><?php esc_html_e( 'No units found.', 'loungenie-portal' ); ?></p>
<?php endif; ?>

<!-- Units Table with Export -->
<div class="lgp-card" id="units-table-wrapper" style="display: none;">
	<div class="lgp-card-header flex justify-between items-center">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Units List', 'loungenie-portal' ); ?></h2>
		<button type="button" class="lgp-btn lgp-btn-primary" id="lgp-export-units">
			📥 <?php esc_html_e( 'Export to CSV', 'loungenie-portal' ); ?>
		</button>
	</div>
	<div class="lgp-card-body">
		<!-- Loading Spinner -->
		<div id="lgp-loading-spinner" class="lgp-loading-spinner" style="display: none;">
			<div class="lgp-spinner"></div>
			<p><?php esc_html_e( 'Loading...', 'loungenie-portal' ); ?></p>
		</div>

		<?php if ( ! empty( $units ) ) : ?>
			<div class="lgp-table-container">
				<table class="lgp-table" id="units-table">
					<thead>
						<tr>
							<th class="sortable"><?php esc_html_e( 'Unit ID', 'loungenie-portal' ); ?></th>
							<th class="sortable"><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="color"><?php esc_html_e( 'Color', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="season"><?php esc_html_e( 'Season', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="venue"><?php esc_html_e( 'Venue', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="lock-brand"><?php esc_html_e( 'Lock Brand', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="status"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Install Date', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $units as $unit ) : ?>
							<tr data-color="<?php echo esc_attr( $unit->color_tag ?? '' ); ?>"
								data-season="<?php echo esc_attr( $unit->season ?? '' ); ?>"
								data-venue="<?php echo esc_attr( $unit->venue_type ?? '' ); ?>"
								data-lock-brand="<?php echo esc_attr( $unit->lock_brand ?? '' ); ?>"
								data-status="<?php echo esc_attr( $unit->status ); ?>">
								<td>#<?php echo esc_html( $unit->id ); ?></td>
								<td><?php echo esc_html( $unit->company_name ); ?></td>
								<td>
									<?php
									if ( $unit->color_tag ) :
										$color_hex = lgp_get_color_hex( $unit->color_tag );
										?>
										<span class="lgp-color-tag">
											<span class="lgp-color-indicator" style="background-color: <?php echo esc_attr( $color_hex ); ?>"></span>
											<?php echo esc_html( $unit->color_tag ); ?>
										</span>
									<?php else : ?>
										<span class="lgp-empty-value">—</span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $unit->season ?? '—' ); ?></td>
								<td><?php echo esc_html( $unit->venue_type ?? '—' ); ?></td>
								<td><?php echo esc_html( $unit->lock_brand ?? '—' ); ?></td>
								<td><?php echo esc_html( $unit->status ); ?></td>
								<td><?php echo $unit->install_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $unit->install_date ) ) ) : '<span class="lgp-empty-value">—</span>'; ?></td>
								<td>
									<div class="lgp-table-actions">
										<a class="lgp-btn lgp-btn-secondary" href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>">
											<?php esc_html_e( 'View', 'loungenie-portal' ); ?>
										</a>
										<a class="lgp-btn lgp-btn-primary" href="<?php echo esc_url( home_url( '/portal/tickets' ) ); ?>">
											<?php esc_html_e( 'Service Request', 'loungenie-portal' ); ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No units found.', 'loungenie-portal' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<div class="lgp-card lgp-card-ghost" style="margin-top: var(--space-md);">
	<div class="lgp-card-header" style="justify-content: space-between; align-items: center;">
		<div>
			<h3 class="lgp-card-title" style="margin-bottom: 4px;"><?php esc_html_e( 'View Options', 'loungenie-portal' ); ?></h3>
			<p class="lgp-text-muted">Use cards for a modern overview or switch to table for dense data.</p>
		</div>
		<div class="lgp-view-toggle" id="lgp-view-toggle">
			<button type="button" class="active" data-view="cards"><?php esc_html_e( 'Card View', 'loungenie-portal' ); ?></button>
			<button type="button" data-view="table"><?php esc_html_e( 'Table View', 'loungenie-portal' ); ?></button>
		</div>
	</div>
</div>

<!-- Advanced Filters -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Filters', 'loungenie-portal' ); ?></h2>
		<button type="button" class="lgp-btn lgp-btn-secondary" id="lgp-clear-filters">
			<?php esc_html_e( 'Clear All Filters', 'loungenie-portal' ); ?>
		</button>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-filters lgp-advanced-filters">
			<!-- Color Filter -->
			<div class="lgp-filter-group">
				<label for="filter-color" class="lgp-label"><?php esc_html_e( 'Color', 'loungenie-portal' ); ?></label>
				<select id="filter-color" class="lgp-select lgp-table-filter" data-filter="color">
					<option value=""><?php esc_html_e( 'All Colors', 'loungenie-portal' ); ?></option>
					<option value="Yellow"><?php esc_html_e( 'Yellow', 'loungenie-portal' ); ?></option>
					<option value="Red"><?php esc_html_e( 'Red', 'loungenie-portal' ); ?></option>
					<option value="Classic Blue"><?php esc_html_e( 'Classic Blue', 'loungenie-portal' ); ?></option>
					<option value="Ice Blue"><?php esc_html_e( 'Ice Blue', 'loungenie-portal' ); ?></option>
				</select>
			</div>

			<!-- Season Filter -->
			<div class="lgp-filter-group">
				<label for="filter-season" class="lgp-label"><?php esc_html_e( 'Season', 'loungenie-portal' ); ?></label>
				<select id="filter-season" class="lgp-select lgp-table-filter" data-filter="season">
					<option value=""><?php esc_html_e( 'All Seasons', 'loungenie-portal' ); ?></option>
					<option value="seasonal"><?php esc_html_e( 'Seasonal', 'loungenie-portal' ); ?></option>
					<option value="year-round"><?php esc_html_e( 'Year-Round', 'loungenie-portal' ); ?></option>
				</select>
			</div>

			<!-- Venue Filter -->
			<div class="lgp-filter-group">
				<label for="filter-venue" class="lgp-label"><?php esc_html_e( 'Venue Type', 'loungenie-portal' ); ?></label>
				<select id="filter-venue" class="lgp-select lgp-table-filter" data-filter="venue">
					<option value=""><?php esc_html_e( 'All Venues', 'loungenie-portal' ); ?></option>
					<option value="Hotel"><?php esc_html_e( 'Hotel', 'loungenie-portal' ); ?></option>
					<option value="Resort"><?php esc_html_e( 'Resort', 'loungenie-portal' ); ?></option>
					<option value="Waterpark"><?php esc_html_e( 'Waterpark', 'loungenie-portal' ); ?></option>
					<option value="Surf Park"><?php esc_html_e( 'Surf Park', 'loungenie-portal' ); ?></option>
					<option value="Others"><?php esc_html_e( 'Others', 'loungenie-portal' ); ?></option>
				</select>
			</div>

			<!-- Lock Brand Filter -->
			<div class="lgp-filter-group">
				<label for="filter-lock-brand" class="lgp-label"><?php esc_html_e( 'Lock Brand', 'loungenie-portal' ); ?></label>
				<select id="filter-lock-brand" class="lgp-select lgp-table-filter" data-filter="lock-brand">
					<option value=""><?php esc_html_e( 'All Brands', 'loungenie-portal' ); ?></option>
					<option value="MAKE"><?php esc_html_e( 'MAKE', 'loungenie-portal' ); ?></option>
					<option value="L&F"><?php esc_html_e( 'L&F', 'loungenie-portal' ); ?></option>
				</select>
			</div>

			<!-- Status Filter -->
			<div class="lgp-filter-group">
				<label for="filter-status" class="lgp-label"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></label>
				<select id="filter-status" class="lgp-select lgp-table-filter" data-filter="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'loungenie-portal' ); ?></option>
					<option value="active"><?php esc_html_e( 'Active', 'loungenie-portal' ); ?></option>
					<option value="install"><?php esc_html_e( 'Installation', 'loungenie-portal' ); ?></option>
					<option value="service"><?php esc_html_e( 'Service', 'loungenie-portal' ); ?></option>
				</select>
			</div>

			<!-- Search -->
			<div class="lgp-search-box">
				<label for="units-search" class="lgp-label"><?php esc_html_e( 'Search', 'loungenie-portal' ); ?></label>
				<input type="text" id="units-search" class="lgp-input lgp-search-input" placeholder="<?php esc_attr_e( 'Search units...', 'loungenie-portal' ); ?>" data-table="units-table">
			</div>
		</div>

		<!-- Active Filters Display -->
		<div id="active-filters" class="lgp-active-filters" style="display: none;">
			<strong><?php esc_html_e( 'Active Filters:', 'loungenie-portal' ); ?></strong>
			<div id="active-filters-list" class="lgp-filter-tags"></div>
		</div>
	</div>
</div>

<!-- Units Table with Export -->
<div class="lgp-card">
	<div class="lgp-card-header flex justify-between items-center">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Units List', 'loungenie-portal' ); ?></h2>
		<button type="button" class="lgp-btn lgp-btn-primary" id="lgp-export-units">
			📥 <?php esc_html_e( 'Export to CSV', 'loungenie-portal' ); ?>
		</button>
	</div>
	<div class="lgp-card-body">
		<!-- Loading Spinner -->
		<div id="lgp-loading-spinner" class="lgp-loading-spinner" style="display: none;">
			<div class="lgp-spinner"></div>
			<p><?php esc_html_e( 'Loading...', 'loungenie-portal' ); ?></p>
		</div>

		<?php if ( ! empty( $units ) ) : ?>
			<div class="lgp-table-container">
				<table class="lgp-table" id="units-table">
					<thead>
						<tr>
							<th class="sortable"><?php esc_html_e( 'Unit ID', 'loungenie-portal' ); ?></th>
							<th class="sortable"><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="color"><?php esc_html_e( 'Color', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="season"><?php esc_html_e( 'Season', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="venue"><?php esc_html_e( 'Venue', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="lock-brand"><?php esc_html_e( 'Lock Brand', 'loungenie-portal' ); ?></th>
							<th class="sortable" data-sort="status"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Install Date', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $units as $unit ) : ?>
							<tr data-color="<?php echo esc_attr( $unit->color_tag ?? '' ); ?>"
								data-season="<?php echo esc_attr( $unit->season ?? '' ); ?>"
								data-venue="<?php echo esc_attr( $unit->venue_type ?? '' ); ?>"
								data-lock-brand="<?php echo esc_attr( $unit->lock_brand ?? '' ); ?>"
								data-status="<?php echo esc_attr( $unit->status ); ?>">
								<td>#<?php echo esc_html( $unit->id ); ?></td>
								<td><?php echo esc_html( $unit->company_name ); ?></td>
								<td>
									<?php
									if ( $unit->color_tag ) :
										$color_hex = lgp_get_color_hex( $unit->color_tag );
										?>
										<span class="lgp-color-tag">
											<span class="lgp-color-indicator" style="background-color: <?php echo esc_attr( $color_hex ); ?>"></span>
											<?php echo esc_html( $unit->color_tag ); ?>
										</span>
									<?php else : ?>
										<span class="lgp-empty-value">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $unit->season ) : ?>
										<span class="lgp-badge lgp-badge-<?php echo esc_attr( $unit->season === 'seasonal' ? 'warning' : 'info' ); ?>">
											<?php echo esc_html( ucfirst( str_replace( '-', ' ', $unit->season ) ) ); ?>
										</span>
									<?php else : ?>
										<span class="lgp-empty-value">—</span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $unit->venue_type ?? '—' ); ?></td>
								<td><?php echo esc_html( $unit->lock_brand ?? '—' ); ?></td>
								<td>
									<?php
									$status_class = 'info';
									if ( $unit->status === 'active' ) {
										$status_class = 'success';
									} elseif ( $unit->status === 'service' ) {
										$status_class = 'warning';
									}
									?>
									<span class="lgp-badge lgp-badge-<?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( ucfirst( $unit->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $unit->install_date ? date_i18n( get_option( 'date_format' ), strtotime( $unit->install_date ) ) : '—' ); ?></td>
								<td>
									<button class="lgp-btn lgp-btn-primary lgp-btn-sm" data-unit-id="<?php echo esc_attr( $unit->id ); ?>">
										<?php esc_html_e( 'View', 'loungenie-portal' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Results Count -->
			<div class="lgp-results-info">
				<span id="visible-count"><?php echo count( $units ); ?></span> <?php esc_html_e( 'of', 'loungenie-portal' ); ?> 
				<span id="total-count"><?php echo count( $units ); ?></span> <?php esc_html_e( 'units', 'loungenie-portal' ); ?>
			</div>
		<?php else : ?>
			<div class="lgp-empty-state-card">
				<p class="lgp-empty-state-icon">📦</p>
				<h3 class="lgp-empty-state-title"><?php esc_html_e( 'No Units Found', 'loungenie-portal' ); ?></h3>
				<p class="lgp-empty-state-text"><?php esc_html_e( 'There are no LounGenie units to display at this time.', 'loungenie-portal' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
