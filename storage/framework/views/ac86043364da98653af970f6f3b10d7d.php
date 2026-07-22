

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div class="page-header__content">
            <div class="feature-badge">Tableau de bord</div>
            <h1 class="page-title">Bienvenue, <?php echo e(auth()->user()->name); ?></h1>
        </div>
        <?php if(auth()->user()->role === 'copropriétaire'): ?>
            <a href="<?php echo e(route('profile.show')); ?>" class="btn-secondary">Infos personnelles</a>
        <?php endif; ?>
    </section>

    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="dashboard-grid">
        <?php if(auth()->user()->role === 'syndic' && $stats): ?>
            <div class="stat-grid">
                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <span class="stat-card__label">Cotisation collectée</span>
                    <span class="stat-card__value"><?php echo e(number_format($stats['contribution_collected'], 0, ',', ' ')); ?> <small>/ <?php echo e(number_format($stats['contribution_expected'], 0, ',', ' ')); ?> MAD</small></span>
                    <div class="stat-card__bar">
                        <div class="stat-card__bar-fill" style="width: <?php echo e($stats['contribution_expected'] > 0 ? min(100, round($stats['contribution_collected'] / $stats['contribution_expected'] * 100)) : 0); ?>%;"></div>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16"/><path d="M7 7v10"/><path d="M17 7v10"/><path d="M4 17h16"/></svg>
                    </span>
                    <span class="stat-card__label">Dépenses du mois</span>
                    <span class="stat-card__value"><?php echo e(number_format($stats['monthly_expenses'], 0, ',', ' ')); ?> <small>MAD</small></span>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/></svg>
                    </span>
                    <span class="stat-card__label">Réclamations en attente</span>
                    <span class="stat-card__value"><?php echo e($stats['pending_complaints']); ?></span>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </span>
                    <span class="stat-card__label">Dernière réunion programmée</span>
                    <?php if($stats['last_planned_meeting']): ?>
                        <span class="stat-card__value" style="font-size:1.05rem;"><?php echo e($stats['last_planned_meeting']->title); ?></span>
                        <span class="stat-card__sub"><?php echo e(\Illuminate\Support\Carbon::parse($stats['last_planned_meeting']->meeting_date)->format('d/m/Y à H:i')); ?></span>
                    <?php else: ?>
                        <span class="stat-card__sub">Aucune réunion programmée</span>
                    <?php endif; ?>
                </article>
            </div>

            <button type="button" id="stats-toggle" class="stats-toggle-link" aria-expanded="false">
                Statistiques
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div id="stats-charts" class="stats-charts">
                <?php
                    $months = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
                    $maxExpense = max(1, max($monthlyExpenseChart));
                    $maxComplaint = max(1, max($monthlyComplaintChart));
                ?>

                <div class="chart-card">
                    <h3>Dépenses par mois (<?php echo e(now()->year); ?>)</h3>
                    <div class="bar-chart">
                        <?php $__currentLoopData = $monthlyExpenseChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bar-chart__col">
                                <div class="bar-chart__bar" style="height: <?php echo e($value > 0 ? max(4, round($value / $maxExpense * 100)) : 2); ?>%;" title="<?php echo e($months[$i]); ?> : <?php echo e(number_format($value, 0, ',', ' ')); ?> MAD"></div>
                                <span class="bar-chart__label"><?php echo e($months[$i]); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Réclamations par mois (<?php echo e(now()->year); ?>)</h3>
                    <div class="bar-chart">
                        <?php $__currentLoopData = $monthlyComplaintChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bar-chart__col">
                                <div class="bar-chart__bar bar-chart__bar--accent" style="height: <?php echo e($value > 0 ? max(4, round($value / $maxComplaint * 100)) : 2); ?>%;" title="<?php echo e($months[$i]); ?> : <?php echo e($value); ?> réclamation(s)"></div>
                                <span class="bar-chart__label"><?php echo e($months[$i]); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if(auth()->user()->role === 'copropriétaire'): ?>
            <article class="dashboard-card">
                <div class="feature-badge">Factures impayées</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Notification</h2>
                <?php if($unpaidInvoices->isEmpty()): ?>
                    <p style="margin:0; color:var(--color-text-muted);">Aucune facture impayée en cours.</p>
                <?php else: ?>
                    <ul style="margin:0.4rem 0 0; padding-left:1rem; color:var(--color-text-muted);">
                        <?php $__currentLoopData = $unpaidInvoices->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($invoice->invoice_number); ?> — <?php echo e(number_format($invoice->amount, 2, ',', ' ')); ?> €</li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </article>

            <article class="dashboard-card">
                <div class="feature-badge">Décision du dernier vote</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière décision</h2>
                <p style="margin:0; color:var(--color-text-muted);"><?php echo e($latestClosedVoteDecision ?? 'Aucune décision disponible pour le moment.'); ?></p>
            </article>

            <article class="dashboard-card">
                <div class="feature-badge">Réunion</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière réunion</h2>
                <?php if($lastMeeting): ?>
                    <p style="margin:0; font-weight:700;"><?php echo e($lastMeeting->title); ?></p>
                    <small><?php echo e(\Illuminate\Support\Carbon::parse($lastMeeting->meeting_date)->format('d/m/Y à H:i')); ?></small>
                    <?php if($meetingReportRoute): ?>
                        <p style="margin:0.4rem 0 0;"><a href="<?php echo e($meetingReportRoute); ?>" class="btn-secondary">Voir le compte rendu</a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="margin:0; color:var(--color-text-muted);">Aucune réunion enregistrée pour le moment.</p>
                <?php endif; ?>
            </article>
        <?php endif; ?>

        <?php if (! (auth()->user()->role === 'syndic')): ?>
            <article class="dashboard-card">
                <div class="feature-badge">Réunions</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière réunion</h2>
                <?php if($lastMeeting): ?>
                    <p style="margin:0; font-weight:700;"><?php echo e($lastMeeting->title); ?></p>
                    <small><?php echo e(\Illuminate\Support\Carbon::parse($lastMeeting->meeting_date)->format('d/m/Y à H:i')); ?></small>
                <?php else: ?>
                    <p style="margin:0; color:var(--color-text-muted);">Aucune réunion enregistrée pour le moment.</p>
                <?php endif; ?>
            </article>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/dashboard.blade.php ENDPATH**/ ?>