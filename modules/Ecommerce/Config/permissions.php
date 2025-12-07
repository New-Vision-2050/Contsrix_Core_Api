<?php

return [
    'permissions' => [
        // ================================================================================================
        // ECOMMERCE MODULE PERMISSIONS
        // E-Commerce Management System
        // ================================================================================================

        // Banner Management
        'ECOMMERCE_BANNER_LIST' => 'ecommerce.banner*banner.list', 
        'ECOMMERCE_BANNER_VIEW' => 'ecommerce.banner*banner.view',
        'ECOMMERCE_BANNER_CREATE' => 'ecommerce.banner*banner.create',
        'ECOMMERCE_BANNER_UPDATE' => 'ecommerce.banner*banner.update',
        'ECOMMERCE_BANNER_ACTIVATE' => 'ecommerce.banner*banner.activate',
        'ECOMMERCE_BANNER_DELETE' => 'ecommerce.banner*banner.delete',
        'ECOMMERCE_BANNER_EXPORT' => 'ecommerce.banner*banner.export',

        // Setting Page Management
        'ECOMMERCE_SETTING_PAGE_LIST' => 'ecommerce.setting-page*setting-page.list',
        'ECOMMERCE_SETTING_PAGE_VIEW' => 'ecommerce.setting-page*setting-page.view',
        'ECOMMERCE_SETTING_PAGE_CREATE' => 'ecommerce.setting-page*setting-page.create',
        'ECOMMERCE_SETTING_PAGE_UPDATE' => 'ecommerce.setting-page*setting-page.update',
        'ECOMMERCE_SETTING_PAGE_ACTIVATE' => 'ecommerce.setting-page*setting-page.activate',
        'ECOMMERCE_SETTING_PAGE_DELETE' => 'ecommerce.setting-page*setting-page.delete',

        // Feature Management
        'ECOMMERCE_FEATURE_LIST' => 'ecommerce.feature*feature.list',
        'ECOMMERCE_FEATURE_VIEW' => 'ecommerce.feature*feature.view',
        'ECOMMERCE_FEATURE_CREATE' => 'ecommerce.feature*feature.create',
        'ECOMMERCE_FEATURE_UPDATE' => 'ecommerce.feature*feature.update',
        'ECOMMERCE_FEATURE_ACTIVATE' => 'ecommerce.feature*feature.activate',
        'ECOMMERCE_FEATURE_DELETE' => 'ecommerce.feature*feature.delete',
        'ECOMMERCE_FEATURE_EXPORT' => 'ecommerce.feature*feature.export',

        // Store Branch Management
        'ECOMMERCE_STORE_BRANCH_LIST' => 'ecommerce.store-branch*store-branch.list',
        'ECOMMERCE_STORE_BRANCH_VIEW' => 'ecommerce.store-branch*store-branch.view',
        'ECOMMERCE_STORE_BRANCH_CREATE' => 'ecommerce.store-branch*store-branch.create',
        'ECOMMERCE_STORE_BRANCH_UPDATE' => 'ecommerce.store-branch*store-branch.update',
        'ECOMMERCE_STORE_BRANCH_ACTIVATE' => 'ecommerce.store-branch*store-branch.activate',
        'ECOMMERCE_STORE_BRANCH_DELETE' => 'ecommerce.store-branch*store-branch.delete',

        // Dashboard Management
        'ECOMMERCE_DASHBOARD_VIEW' => 'ecommerce.dashboard*dashboard.view',
        'ECOMMERCE_DASHBOARD_ORDERS_CHART' => 'ecommerce.dashboard*dashboard.orders-chart',
        'ECOMMERCE_DASHBOARD_WAREHOUSES_TABLE' => 'ecommerce.dashboard*dashboard.warehouses-table',

        // Coupon Management
        'ECOMMERCE_COUPON_LIST' => 'ecommerce.coupon*coupon.list',
        'ECOMMERCE_COUPON_VIEW' => 'ecommerce.coupon*coupon.view',
        'ECOMMERCE_COUPON_CREATE' => 'ecommerce.coupon*coupon.create',
        'ECOMMERCE_COUPON_UPDATE' => 'ecommerce.coupon*coupon.update',
        'ECOMMERCE_COUPON_ACTIVATE' => 'ecommerce.coupon*coupon.activate',
        'ECOMMERCE_COUPON_DELETE' => 'ecommerce.coupon*coupon.delete',
        'ECOMMERCE_COUPON_EXPORT' => 'ecommerce.coupon*coupon.export',

        // Deal Day Management
        'ECOMMERCE_DEAL_DAY_LIST' => 'ecommerce.deal-day*deal-day.list',
        'ECOMMERCE_DEAL_DAY_VIEW' => 'ecommerce.deal-day*deal-day.view',
        'ECOMMERCE_DEAL_DAY_CREATE' => 'ecommerce.deal-day*deal-day.create',
        'ECOMMERCE_DEAL_DAY_UPDATE' => 'ecommerce.deal-day*deal-day.update',
        'ECOMMERCE_DEAL_DAY_ACTIVATE' => 'ecommerce.deal-day*deal-day.activate',
        'ECOMMERCE_DEAL_DAY_DELETE' => 'ecommerce.deal-day*deal-day.delete',
        'ECOMMERCE_DEAL_DAY_EXPORT' => 'ecommerce.deal-day*deal-day.export',

        // Eco Brand Management
        'ECOMMERCE_ECO_BRAND_LIST' => 'ecommerce.eco-brand*eco-brand.list',
        'ECOMMERCE_ECO_BRAND_VIEW' => 'ecommerce.eco-brand*eco-brand.view',
        'ECOMMERCE_ECO_BRAND_CREATE' => 'ecommerce.eco-brand*eco-brand.create',
        'ECOMMERCE_ECO_BRAND_UPDATE' => 'ecommerce.eco-brand*eco-brand.update',
        'ECOMMERCE_ECO_BRAND_ACTIVATE' => 'ecommerce.eco-brand*eco-brand.activate',
        'ECOMMERCE_ECO_BRAND_DELETE' => 'ecommerce.eco-brand*eco-brand.delete',
        'ECOMMERCE_ECO_BRAND_EXPORT' => 'ecommerce.eco-brand*eco-brand.export',

        // Eco Category Management
        'ECOMMERCE_CATEGORY_LIST' => 'ecommerce.category*category.list',
        'ECOMMERCE_CATEGORY_VIEW' => 'ecommerce.category*category.view',
        'ECOMMERCE_CATEGORY_CREATE' => 'ecommerce.category*category.create',
        'ECOMMERCE_CATEGORY_UPDATE' => 'ecommerce.category*category.update',
        'ECOMMERCE_CATEGORY_ACTIVATE' => 'ecommerce.category*category.activate',
        'ECOMMERCE_CATEGORY_DELETE' => 'ecommerce.category*category.delete',
        'ECOMMERCE_CATEGORY_EXPORT' => 'ecommerce.category*category.export',

        // Product Management
        'ECOMMERCE_PRODUCT_LIST' => 'ecommerce.product*product.list',
        'ECOMMERCE_PRODUCT_VIEW' => 'ecommerce.product*product.view',
        'ECOMMERCE_PRODUCT_CREATE' => 'ecommerce.product*product.create',
        'ECOMMERCE_PRODUCT_UPDATE' => 'ecommerce.product*product.update',
        'ECOMMERCE_PRODUCT_ACTIVATE' => 'ecommerce.product*product.activate',
        'ECOMMERCE_PRODUCT_DELETE' => 'ecommerce.product*product.delete',
        'ECOMMERCE_PRODUCT_EXPORT' => 'ecommerce.product*product.export',

        // Feature Deal Management
        'ECOMMERCE_FEATURE_DEAL_LIST' => 'ecommerce.feature-deal*feature-deal.list',
        'ECOMMERCE_FEATURE_DEAL_VIEW' => 'ecommerce.feature-deal*feature-deal.view',
        'ECOMMERCE_FEATURE_DEAL_CREATE' => 'ecommerce.feature-deal*feature-deal.create',
        'ECOMMERCE_FEATURE_DEAL_UPDATE' => 'ecommerce.feature-deal*feature-deal.update',
        'ECOMMERCE_FEATURE_DEAL_ACTIVATE' => 'ecommerce.feature-deal*feature-deal.activate',
        'ECOMMERCE_FEATURE_DEAL_DELETE' => 'ecommerce.feature-deal*feature-deal.delete',
        'ECOMMERCE_FEATURE_DEAL_EXPORT' => 'ecommerce.feature-deal*feature-deal.export',

        // Flash Deal Management
        'ECOMMERCE_FLASH_DEAL_LIST' => 'ecommerce.flash-deal*flash-deal.list',
        'ECOMMERCE_FLASH_DEAL_VIEW' => 'ecommerce.flash-deal*flash-deal.view',
        'ECOMMERCE_FLASH_DEAL_CREATE' => 'ecommerce.flash-deal*flash-deal.create',
        'ECOMMERCE_FLASH_DEAL_UPDATE' => 'ecommerce.flash-deal*flash-deal.update',
        'ECOMMERCE_FLASH_DEAL_ACTIVATE' => 'ecommerce.flash-deal*flash-deal.activate',
        'ECOMMERCE_FLASH_DEAL_DELETE' => 'ecommerce.flash-deal*flash-deal.delete',
        'ECOMMERCE_FLASH_DEAL_EXPORT' => 'ecommerce.flash-deal*flash-deal.export',

        // Order Management
        'ECOMMERCE_ORDER_LIST' => 'ecommerce.order*order.list',
        'ECOMMERCE_ORDER_VIEW' => 'ecommerce.order*order.view',
        'ECOMMERCE_ORDER_CREATE' => 'ecommerce.order*order.create',
        'ECOMMERCE_ORDER_UPDATE' => 'ecommerce.order*order.update',
        'ECOMMERCE_ORDER_DELETE' => 'ecommerce.order*order.delete',
        'ECOMMERCE_ORDER_EXPORT' => 'ecommerce.order*order.export',
        'ECOMMERCE_ORDER_UPDATE_STATUS' => 'ecommerce.order*order.update-status',
    ]
];

