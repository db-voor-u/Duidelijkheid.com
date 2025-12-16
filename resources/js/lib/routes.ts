// Route definitions and helpers. Admin routes zijn Nederlandse paden; builders return strings.

export const adminRoutes = {
    dashboard: '/hoofdbeheerder/dashboard',
    login: '/hoofdbeheerder/login',
    logout: '/hoofdbeheerder/logout',
    forgotPassword: '/hoofdbeheerder/forgot-password',
    resetPassword: '/hoofdbeheerder/reset-password',
    settings: '/hoofdbeheerder/instellingen',
    settingsProfile: '/hoofdbeheerder/instellingen/profiel',
    settingsPassword: '/hoofdbeheerder/instellingen/wachtwoord',
    settingsAppearance: '/hoofdbeheerder/instellingen/uiterlijk',
    users: '/hoofdbeheerder/gebruikers',
    logs: '/hoofdbeheerder/logs',
    database: '/hoofdbeheerder/database',
    welcomeContent: '/hoofdbeheerder/pages/welcome',
    overOnsContent: '/hoofdbeheerder/pages/over-ons',
    innovatieContent: '/hoofdbeheerder/pages/innovatie',
    termsContent: '/hoofdbeheerder/pages/terms',
    privacyContent: '/hoofdbeheerder/pages/privacy',

    // Blog (hoofd blog)
    blogContent: '/hoofdbeheerder/pages/blog',
    blog: '/hoofdbeheerder/blog',
    blogCreate: '/hoofdbeheerder/blog/aanmaken',
    blogStore: '/hoofdbeheerder/blog',

    // 👇 Over Ons Blog Routes
    overonsBlog: '/hoofdbeheerder/over-ons-blog',             // Index
    overonsBlogCreate: '/hoofdbeheerder/over-ons-blog/aanmaken', // Create
    overonsBlogStore: '/hoofdbeheerder/over-ons-blog',        // Store (POST)



    contact: '/hoofdbeheerder/contact',
    contactCreate: '/hoofdbeheerder/contact/nieuw',
    contactCompose: '/hoofdbeheerder/contact/opstellen',
    contactSend: '/hoofdbeheerder/contact/send',
    contactPage: '/hoofdbeheerder/pages/contact',
} as const;

// Admin route builders (dynamisch, met id/slug)
export const adminRouteFns = {
    blogEdit: (slug: string) => `/hoofdbeheerder/blog/${slug}/bewerken`,
    blogUpdate: (slug: string) => `/hoofdbeheerder/blog/${slug}`,
    blogDestroy: (slug: string) => `/hoofdbeheerder/blog/${slug}`,

    // Gebruik slug i.p.v. id
    overonsBlogEdit: (slug: string) =>
        `/hoofdbeheerder/over-ons-blog/${slug}/bewerken`,
    overonsBlogUpdate: (slug: string) =>
        `/hoofdbeheerder/over-ons-blog/${slug}`,
    overonsBlogDestroy: (slug: string) =>
        `/hoofdbeheerder/over-ons-blog/${slug}`,



    // Contact actions (admin)
    contactShow: (id: number | string) => `/hoofdbeheerder/contact/${id}`,
    contactReply: (id: number | string) =>
        `/hoofdbeheerder/contact/${id}/reply`,
    contactStatus: (id: number | string) =>
        `/hoofdbeheerder/contact/${id}/status`,
    contactDestroy: (id: number | string) => `/hoofdbeheerder/contact/${id}`,
    contactRestore: (id: number | string) =>
        `/hoofdbeheerder/contact/${id}/restore`,
} as const;

// User Routes
export const userRoutes = {
    dashboard: '/dashboard',
} as const;

// Auth Routes
export const authRoutes = {
    login: '/hoofdbeheerder/login',
    register: '/register',
    logout: '/logout',
    profile: '/profile',
    settings: '/settings',
    forgotPassword: '/forgot-password',
    resetPassword: '/reset-password',
} as const;

// Publieke blog (alleen strings)
export const blogRoutes = {
    index: '/blog',
} as const;

// Publieke blog route builders
export const blogRouteFns = {
    show: (slug: string) => `/blog/${slug}`,
    // Publieke Over Ons Blog route builder
    overonsShow: (slug: string) => `/over-ons-blog/${slug}`,
} as const;

// Publieke pages (contact)
export const pageRoutes = {
    contact: '/contact',
    overOns: '/over-ons',
} as const;

// Alle routes samen
export const routes = {
    admin: adminRoutes,
    adminFns: adminRouteFns,
    welcome: adminRoutes.welcomeContent,
    blog: blogRoutes,
    blogFns: blogRouteFns,
    pages: pageRoutes,
    user: userRoutes,
    auth: authRoutes,
    home: '/',
} as const;

// Types
export type AdminRoute = typeof adminRoutes[keyof typeof adminRoutes];
export type UserRoute = typeof userRoutes[keyof typeof userRoutes];
export type AuthRoute = typeof authRoutes[keyof typeof authRoutes];
export type BlogRoute = typeof blogRoutes[keyof typeof blogRoutes];
export type PageRoute = typeof pageRoutes[keyof typeof pageRoutes];

export type AdminRouteFn = typeof adminRouteFns[keyof typeof adminRouteFns];
export type AppRoute =
    | AdminRoute
    | UserRoute
    | AuthRoute
    | BlogRoute
    | PageRoute
    | string;

export type AppRoutes = typeof routes;
