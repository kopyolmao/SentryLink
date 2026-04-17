<?php shell_start('SentryLink | Broadcast', $user, 'admin', 'broadcast', 'Notification Broadcast', 'Send notices to all users or filtered groups.'); ?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<div class="panel">
    <form method="POST" class="row g-3">
        <div class="col-md-3"><label class="form-label">Role</label><select class="form-select" name="role"><option value="">All roles</option><?php foreach (['student', 'ssg', 'admin', 'director'] as $role): ?><option value="<?= h($role) ?>"><?= h(ucfirst($role)) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Course</label><input class="form-control" name="course"></div>
        <div class="col-md-3"><label class="form-label">Year Level</label><input class="form-control" name="year_level"></div>
        <div class="col-md-3"><label class="form-label">House</label><input class="form-control" name="house"></div>
        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
        <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
        <div class="col-12"><button class="btn btn-primary">Send Broadcast</button></div>
    </form>
</div>
<?php shell_end(); ?>
