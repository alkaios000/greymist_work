<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Greymist</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #F8FAFC;
            --bg-sidebar: #FFFFFF;
            --bg-card: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-soft: #E2E8F0;
            --accent: #3B82F6;
            --accent-hover: #2563EB;
            --accent-light: #EFF6FF;
            --success: #22C55E;
            --success-light: #DCFCE7;
            --font-heading: 'Inter', sans-serif;
            --shadow-soft: 0 4px 20px rgba(59, 130, 246, 0.05);
            --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.03);
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-heading);
            background: var(--bg-main);
            color: var(--text-main);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            color: var(--text-main);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
            transform: translateX(0);
            border-right: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(2px);
            z-index: 999;
        }
        
        .sidebar-overlay.active { display: block; }
        
        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(108, 72, 245, 0.3);
        }
        
        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }
        
        .nav-section {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #94A3B8;
            font-weight: 600;
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
            margin-top: 1.5rem;
            letter-spacing: 0.5px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.85rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .nav-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-nav a:hover {
            color: var(--accent);
            background: rgba(108, 72, 245, 0.04);
        }
        
        .sidebar-nav a.active {
            color: var(--accent);
            background: var(--accent-light);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        
        .sidebar-nav i { font-size: 1rem; width: 20px; text-align: center; }
        
        .nav-badge {
            background: var(--accent);
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 99px;
            font-weight: 700;
        }
        
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-soft);
        }
        
        .trial-card {
            background: var(--accent-light);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .trial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 8px;
        }
        
        .trial-badge {
            background: var(--accent);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.65rem;
        }
        
        .progress-bar {
            height: 4px;
            background: rgba(108, 72, 245, 0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--accent);
            width: 66%;
            border-radius: 4px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--text-main);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }
        
        .user-role {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem 3rem;
            transition: margin-left 0.3s ease;
            max-width: 1400px;
        }
        
        /* Sensei Top Navigation */
        .sensei-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: transparent;
        }
        
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .breadcrumbs i { font-size: 0.8rem; }
        .breadcrumbs .active { color: var(--text-main); font-weight: 600; }
        
        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .search-input {
            background: white;
            border: 1px solid var(--border-soft);
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            width: 250px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%2394A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat;
            background-position: 10px center;
            box-shadow: var(--shadow-soft);
        }
        
        .search-input:focus {
            border-color: var(--accent);
        }
        
        .btn-outline {
            background: white;
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-outline:hover {
            background: var(--accent-light);
        }
        
        .btn-solid {
            background: var(--accent);
            border: 1px solid var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(108, 72, 245, 0.2);
        }
        
        .btn-solid:hover {
            background: var(--accent-hover);
        }
        
        /* Section Titles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            margin-top: 2.5rem;
        }
        
        .section-title h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        
        .section-title p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        /* Overview Cards (Sensei Style) */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        .overview-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-card);
            transition: all 0.3s ease;
        }
        
        .overview-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-color: rgba(108, 72, 245, 0.2);
        }
        
        .card-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 1.5rem;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            background: #F8F9FA;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 1px solid #E5E7EB;
        }
        
        .card-title-area h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }
        
        .card-title-area p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .card-body {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -1px;
        }
        
        .card-trend {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .card-footer {
            border-top: 1px solid var(--border-soft);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-main);
            text-decoration: none;
        }
        
        .card-footer i {
            color: var(--text-muted);
            transition: transform 0.2s;
        }
        
        .overview-card:hover .card-footer i {
            transform: translateX(4px);
            color: var(--accent);
        }
        
        /* Top Market Demands (CSS Chart Simulation) */
        .market-demands {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            position: relative;
            padding: 1.5rem;
        }
        
        .demands-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-soft);
            padding-bottom: 0.5rem;
        }
        
        .demand-tab {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            border: 1px solid transparent;
        }
        
        .demand-tab.active {
            background: white;
            border-color: var(--border-soft);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            z-index: 2;
        }
        
        .tab-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .demand-tab.active .tab-icon {
            background: var(--accent);
            color: white;
        }
        
        .demand-tab:not(.active) .tab-icon {
            background: white;
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
        }
        
        .tab-content h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent);
        }
        
        .demand-tab:not(.active) .tab-content h4 { color: var(--text-muted); font-weight: 500; }
        
        .tab-content p { font-size: 0.7rem; color: var(--text-muted); }
        
        .tab-stats {
            margin-top: 1rem;
            display: none;
        }
        
        .demand-tab.active .tab-stats {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .tab-stats h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-main); }
        
        /* CSS Wave Chart Simulation */
        .chart-visual {
            height: 180px;
            position: relative;
            width: 100%;
            background-image: 
                linear-gradient(rgba(240, 240, 255, 0.5) 1px, transparent 1px),
                linear-gradient(90deg, rgba(240, 240, 255, 0.5) 1px, transparent 1px);
            background-size: 40px 40px;
            border-radius: 12px;
            overflow: hidden;
            margin-top: -2rem;
        }
        
        .chart-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1000 200" xmlns="http://www.w3.org/2000/svg"><path d="M0 120 C 150 120, 250 80, 400 90 C 550 100, 700 150, 850 130 C 950 115, 1000 90, 1000 90 L 1000 200 L 0 200 Z" fill="rgba(108, 72, 245, 0.15)"/><path d="M0 150 C 200 150, 300 110, 450 120 C 600 130, 750 180, 900 150 C 950 140, 1000 120, 1000 120 L 1000 200 L 0 200 Z" fill="%236C48F5"/></svg>') no-repeat bottom;
            background-size: 100% 100%;
        }
        
        /* Data Table (Sensei Style) */
        .sensei-table-container {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-card);
            overflow-x: auto;
        }
        
        .sensei-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        .sensei-table th {
            text-align: left;
            padding: 1rem 1.5rem;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-soft);
        }
        
        .sensei-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-soft);
            vertical-align: middle;
        }
        
        .sensei-table tr:last-child td { border-bottom: none; }
        .sensei-table tr:hover { background: #F8FAFC; }
        
        .td-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .td-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text-main);
            border: 1px solid var(--border-soft);
        }
        
        .td-info h4 { font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 2px; }
        .td-info p { font-size: 0.75rem; color: var(--text-muted); }
        
        .td-bold { font-weight: 600; color: var(--text-main); }
        .td-muted { color: var(--text-muted); font-size: 0.75rem; }
        
        /* Legacy Support (To not break users.php / projects.php completely) */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; background: white; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow-card); }
        .data-table th { text-align: left; padding: 1rem; background: var(--bg-main); border-bottom: 1px solid var(--border-soft); }
        .data-table td { padding: 1rem; border-bottom: 1px solid var(--border-soft); }
        .top-bar { display: none; } /* Hide old top bar */
        
        @media (max-width: 1024px) {
            .overview-grid { grid-template-columns: repeat(2, 1fr); }
            .demands-tabs { flex-wrap: wrap; }
        }
        
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; padding: 1.5rem; }
            .overview-grid { grid-template-columns: 1fr; }
            .sensei-top-bar { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .top-actions { width: 100%; justify-content: space-between; }
            .admin-project-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
        }

    /* Modern Project Cards (Floating Title Container) */
    .gm-project-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    .gm-project-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        aspect-ratio: 4/3;
        border: 1px solid rgba(0,0,0,0.05);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }
    .gm-project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(13, 110, 253, 0.1);
        border-color: rgba(13, 110, 253, 0.2);
    }
    .card-image-wrapper {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background: #f1f5f9;
    }
    .card-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Overlay Title Container (Bottom Left) */
    .card-bottom {
        z-index: 5;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        padding: 8px 12px;
        border-top-right-radius: 16px;
        max-width: 80%;
        width: fit-content;
        display: flex;
        flex-direction: column;
        gap: 2px;
        border-top: 1px solid rgba(0,0,0,0.02);
        border-right: 1px solid rgba(0,0,0,0.02);
    }
    .card-titles {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .card-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.7rem;
        color: var(--text-muted);
    }
    .card-meta .meta-creator {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
    }
    
    /* Date moved to top-left inside photo */
    .meta-date {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 10;
        font-size: 0.65rem;
        color: #ffffff;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        padding: 2px 6px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Floating Menu Context (Top Right) */
    .card-menu {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 15;
    }
    .menu-trigger {
        background: transparent;
        border: none;
        color: #ffffff;
        text-shadow: 0 1px 4px rgba(0,0,0,0.8);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: none;
    }
    .menu-trigger:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        transform: scale(1.1);
    }
    
    .card-dropdown {
        position: absolute;
        bottom: 110%;
        right: 0;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border: 1px solid var(--border-soft);
        width: 180px;
        padding: 0.5rem;
        z-index: 100;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), 
                    visibility 0.2s cubic-bezier(0.16, 1, 0.3, 1), 
                    transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .card-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        width: 100%;
        padding: 0.7rem 0.8rem;
        background: none;
        border: none;
        text-align: left;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .dropdown-item i {
        width: 1rem;
        text-align: center;
        font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
        background: #f8fafc;
        color: var(--text-main);
    }
    
    .dropdown-item.danger {
        color: #ef4444;
    }
    .dropdown-item.danger i {
        color: #ef4444;
    }
    .dropdown-item.danger:hover {
        background: #fef2f2;
    }
    
    /* Global Badges for cards */
    .gm-project-card .badge {
        position: absolute;
        z-index: 10;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: none;
    }
    .badge-template { 
        bottom: 8px;
        right: 8px;
        top: auto !important; left: auto !important;
        background: linear-gradient(135deg, #f59e0b, #fbbf24) !important; 
        color: white !important; 
        width: 26px;
        height: 26px;
        border-radius: 50%;
        padding: 0;
        font-size: 0 !important; /* Hide text */
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3) !important;
    }
    .badge-template i { font-size: 0.75rem; margin: 0; }
    
    .gm-project-card .badge-owner ~ .badge-template,
    .gm-project-card .badge-muted ~ .badge-template {
        bottom: 40px;
    }
    .badge-owner, .badge-muted, .badge.project { 
        bottom: 8px; 
        right: 8px;
        top: auto !important; left: auto !important;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        padding: 0;
        font-size: 0 !important; /* Hide text */
        background: rgba(255,255,255,0.95) !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .badge-owner i, .badge.project i { font-size: 0.75rem; color: #15803d; margin: 0; }
    .badge-muted i { font-size: 0.75rem; color: #64748b; margin: 0; }

        .admin-card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-soft);
            padding-top: 0.75rem;
            margin-top: auto;
        }
        .admin-card-actions .btn-icon {
            padding: 6px;
            border-radius: 6px;
        }

        /* Modern Modals */
        .modal-glass {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-glass[style*="display: flex"], .modal-glass[style*="display:flex"], .modal-glass.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-glass-content {
            background: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: scale(0.95) translateY(10px);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: left;
            position: relative;
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .modal-glass[style*="display: flex"] .modal-glass-content, 
        .modal-glass[style*="display:flex"] .modal-glass-content,
        .modal-glass.show .modal-glass-content {
            transform: scale(1) translateY(0);
        }

        .modal-icon { display: none; }

        .modal-glass-content h3 {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
            padding: 1.5rem 1.5rem 0.5rem;
            color: #0f172a;
        }

        .modal-glass-body {
            padding: 0 1.5rem 1.5rem;
        }

        .modal-glass-body p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }

        .modal-glass-body p + p {
            margin-top: 0.5rem;
        }

        .modal-glass-body strong {
            color: #0f172a;
            font-weight: 600;
        }

        .modal-glass-footer {
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            margin: 0;
        }

        .btn-cancel,
        .btn-confirm {
            flex: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-cancel {
            background: transparent;
            color: #64748b;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-danger {
            background: #ef4444 !important;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .btn-confirm {
            color: white;
        }

        .btn-confirm:hover {
            filter: brightness(0.9);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Thumbnail Loader */
        .thumbnail-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            z-index: 2;
            transition: opacity 0.3s;
        }
        .thumbnail-spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #cbd5e1;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Live Thumbnail */
        .live-thumbnail-wrapper {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 3;
        }

        .mini-preview {
            background: #ffffff;
            pointer-events: none;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>