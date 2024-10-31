<div id="jobamatic-content">
	<?php if($data): ?>
	<div id="jobamatic-status">Page <?php print $data->getCurrentPage(); ?> of <?php print $data->getTotalPages(); ?></div>
<?php while($job = $data->has_next()): ?>
	<div class="jobamatic-job">
		<h3 class="job-title">
			<?php if($job->getURL()): ?>
				<a href="<?php print $job->getURL(); ?>" target="_blank"><?php print $job->getTitle(); ?></a>
			<?php else: ?>
				<?php print $job->getTitle(); ?>
			<?php endif; ?>
		</h3>
		<div class="job-location"><span class="label">Location:</span> <?php print $job->getLocation(TRUE); ?></div>
		<div class="job-source"><span class="label">Source:</span> <?php print $job->getSource(); ?></div>
		<div class="job-type"><span class="label">Job Type:</span> <?php print $job->getType(); ?></div>
		<div class="job-post-date"><span class="label">Date Posted:</span> <?php print date(get_option('date_format'), $job->getDatePosted()); ?></div>
		<div class="job-description"><span class="label">Description:</span> <?php print $job->getExcerpt(); ?></div>
		<div class="more clearfix"><a href="<?php print $job->getURL(); ?>" target="_blank">Full description</a></div>
	</div>
<?php endwhile; ?>
	<?php else: ?>
		<p>No results found.</p>
	<?php endif; ?>
</div>
<?php if($pager): ?>
	<div id="jobamatic-pager"><?php print $pager; ?></div>
<?php endif; ?>
