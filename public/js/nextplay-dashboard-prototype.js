/* Dashboard interactions and single-request reporting-period updates. No polling. */
(() => {
    'use strict';
    const initDashboard = root => {
        if (!root) return;
    let rows = [];
    try { rows = JSON.parse(root.dataset.dashboardChart || '[]'); } catch (_) { rows = []; }
    const currency = root.dataset.dashboardCurrency || '';
    const svg = root.querySelector('[data-sales-svg]');
    const poly = root.querySelector('[data-sales-line]');
    const fill = root.querySelector('[data-sales-fill]');
    const total = root.querySelector('[data-chart-total]');
    const summary = root.querySelector('[data-chart-summary]');
    const tip = root.querySelector('[data-chart-tooltip]');
    const metrics = {
        revenue: {label:'Paid order revenue',format:n => `${currency} ${n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`},
        orders: {label:'Orders placed',format:n => n.toLocaleString()},
        customers: {label:'New customer accounts',format:n => n.toLocaleString()},
    };
    const pointsFor = (values,width,height) => {
        const max = Math.max(1, ...values);
        return values.map((value,index) => ({x: values.length <= 1 ? 0 : index/(values.length-1)*width, y:height-6-(value/max)*(height-18)}));
    };
    const renderChart = (metric) => {
        if (!metrics[metric] || !poly || !fill) return;
        const values = rows.map(item => Number(item[metric]) || 0);
        const points = pointsFor(values,530,160);
        poly.setAttribute('points',points.map(point=>`${point.x.toFixed(1)},${point.y.toFixed(1)}`).join(' '));
        fill.setAttribute('d', points.length ? `M${points[0].x.toFixed(1)},160 L`+points.map(p=>`${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' L')+` L${points[points.length-1].x.toFixed(1)},160 Z` : '');
        total.textContent = metrics[metric].format(values.reduce((acc,value)=>acc+value,0));
        summary.textContent = metrics[metric].label;
        root.querySelectorAll('[data-chart-tab]').forEach(button=>{const active=button.dataset.chartTab===metric;button.classList.toggle('is-active',active);button.setAttribute('aria-pressed',String(active));});
        svg.onmousemove = event => {
            if (!rows.length) return;
            const rect=svg.getBoundingClientRect();
            const offset=Math.min(Math.max((event.clientX-rect.left)/rect.width,0),1);
            const index=Math.round(offset*(rows.length-1));
            tip.textContent=`${rows[index].day}: ${metrics[metric].format(values[index])}`;
            tip.classList.add('is-visible');
        };
        svg.onmouseleave = () => tip.classList.remove('is-visible');
    };
    root.querySelectorAll('[data-chart-tab]').forEach(button=>button.addEventListener('click',()=>renderChart(button.dataset.chartTab)));
    renderChart('revenue');
    root.querySelectorAll('[data-sparkline]').forEach(spark => {
        const metric = spark.dataset.sparkline;
        const values = metric==='quotes' ? rows.map(x=>x.orders) : rows.map(x=>Number(x[metric])||0);
        const points=pointsFor(values,100,28);
        spark.querySelector('polyline')?.setAttribute('points',points.map(p=>`${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' '));
    });
    const matchOrder = (row,filter) => {
        const status=row.dataset.orderStatus || '';
        const payment=row.dataset.orderPayment || '';
        if (filter === 'all') return true;
        if (filter === 'paid') return payment === 'paid';
        if (filter === 'pending') return payment === 'pending' || status === 'pending_payment';
        if (filter === 'processing') return ['design_review','proof_approval','in_production','payment_review'].includes(status);
        if (filter === 'delivered') return status === 'delivered' || status === 'completed';
        if (filter === 'shipped') return ['shipped','partially_shipped'].includes(status);
        if (filter === 'cancelled') return status === 'cancelled';
        return true;
    };
    const filterOrders = (filter,source) => {
        let count = 0;
        root.querySelectorAll('[data-order-row]').forEach(row=>{const show=matchOrder(row,filter);row.hidden=!show;if(show)count++;});
        const label=root.querySelector('[data-orders-label]');
        if (label) label.textContent = `Showing ${count} recent orders${filter==='all'?'':` · ${filter}`}`;
        root.querySelectorAll('[data-orders-filter]').forEach(button=>{const active=button.dataset.ordersFilter===filter;button.classList.toggle('is-active',active);button.setAttribute('aria-pressed',String(active));});
        root.querySelectorAll('[data-status-group]').forEach(button=>button.classList.toggle('is-active',button===source));
    };
    root.querySelectorAll('[data-orders-filter]').forEach(button=>button.addEventListener('click',()=>filterOrders(button.dataset.ordersFilter,null)));
    root.querySelectorAll('[data-status-group]').forEach(button=>button.addEventListener('click',()=>{const status=button.dataset.statusGroup;filterOrders(status==='processing'?'processing':status,button);root.querySelector('.np-dash-recent')?.scrollIntoView({behavior:'smooth',block:'nearest'});}));
    const ring = root.querySelector('.np-dash-ring');
    if (ring) {
        let counts = [0,0,0,0];
        try { counts = JSON.parse(ring.dataset.ringCounts || '[0,0,0,0]'); } catch (_) { /* fallback */ }
        const totalSegments = counts.reduce((sum,value)=>sum+Number(value),0);
        const labels = ['processing','delivered','shipped','cancelled'];
        const selectRingSegment = position => {
            if (!totalSegments) return;
            const target = Math.min(totalSegments-0.001,Math.max(0,position*totalSegments));
            let cumulative=0;
            for (let index=0; index<counts.length; index++) {
                cumulative+=Number(counts[index]);
                if (target<cumulative) {
                    const button=root.querySelector(`[data-status-group="${labels[index]}"]`);
                    filterOrders(labels[index],button);
                    return;
                }
            }
        };
        ring.addEventListener('click',event=>{
            const rect=ring.getBoundingClientRect();
            const angle=(Math.atan2(event.clientY-(rect.top+rect.height/2),event.clientX-(rect.left+rect.width/2))*180/Math.PI+450)%360;
            selectRingSegment(angle/360);
        });
        ring.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();selectRingSegment(0);}});
    }
    root.querySelector('[data-clear-orders]')?.addEventListener('click',()=>filterOrders('all',null));
    };
    initDashboard(document.querySelector('.np-dashboard'));

    // Only the dashboard fragment is fetched; the shared sidebar and header
    // remain mounted and retain focus, scroll position and component state.
    let pending = null;
    let pendingDays = null;
    const loadPeriod = async (days, updateHistory = true) => {
        const current = document.querySelector('.np-dashboard');
        if (!current) return;
        const form = current.querySelector('[data-dashboard-period-form]');
        if (!form) return;
        const oldDays = current.dataset.dashboardDays || '30';
        if (pending && pendingDays === String(days)) return;
        if (!pending && String(days) === oldDays) return;
        const url = new URL(form.action, window.location.href);
        url.searchParams.set('days', String(days));
        pending?.abort();
        const request = new AbortController();
        pending = request;
        pendingDays = String(days);
        current.setAttribute('aria-busy', 'true');
        form.querySelector('select')?.setAttribute('aria-disabled', 'true');
        const selectedMetric = current.querySelector('[data-chart-tab].is-active')?.dataset.chartTab || 'revenue';
        const selectedOrderFilter = current.querySelector('[data-orders-filter].is-active')?.dataset.ordersFilter || 'all';
        try {
            const response = await fetch(url.toString(), {
                headers: {'X-NextPlay-Dashboard': 'partial', 'Accept': 'text/html'},
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Dashboard update failed');
            const markup = await response.text();
            if (request.signal.aborted) return;
            const fragment = document.createElement('template');
            fragment.innerHTML = markup.trim();
            const next = fragment.content.querySelector('.np-dashboard');
            if (!next) throw new Error('Invalid dashboard response');
            current.replaceWith(next);
            initDashboard(next);
            if (selectedMetric !== 'revenue') next.querySelector(`[data-chart-tab="${selectedMetric}"]`)?.click();
            if (selectedOrderFilter !== 'all') next.querySelector(`[data-orders-filter="${selectedOrderFilter}"]`)?.click();
            if (updateHistory) window.history.pushState({nextplayDashboardDays: days}, '', url.pathname + url.search);
        } catch (error) {
            if (request.signal.aborted) return;
            const select = form.querySelector('select');
            if (select) select.value = oldDays;
            // Keep existing dashboard data visible and allow a retry without
            // navigating away or unexpectedly resetting the admin shell.
            let message = current.querySelector('[data-dashboard-refresh-error]');
            if (!message) {
                message = document.createElement('span');
                message.dataset.dashboardRefreshError = '';
                message.className = 'np-dash-refresh-error';
                message.setAttribute('role', 'alert');
                form.insertAdjacentElement('afterend', message);
            }
            message.textContent = 'Unable to update the dashboard. Please try again.';
        } finally {
            if (pending === request) {
                pending = null;
                pendingDays = null;
                const active = document.querySelector('.np-dashboard');
                active?.removeAttribute('aria-busy');
                active?.querySelector('[data-dashboard-period-form] select')?.removeAttribute('aria-disabled');
            }
        }
    };
    document.addEventListener('change', event => {
        if (event.target.matches('[data-dashboard-period-form] select')) {
            loadPeriod(event.target.value);
        }
    });
    document.addEventListener('submit', event => {
        if (event.target.matches('[data-dashboard-period-form]')) {
            event.preventDefault();
            loadPeriod(event.target.querySelector('select')?.value || '30');
        }
    });
    window.addEventListener('popstate', () => {
        const days = new URL(window.location.href).searchParams.get('days') || '30';
        loadPeriod(days, false);
    });
})();
