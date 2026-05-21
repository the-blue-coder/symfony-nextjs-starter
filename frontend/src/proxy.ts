import { clerkMiddleware, createRouteMatcher } from "@clerk/nextjs/server";
import createIntlMiddleware from "next-intl/middleware";
import { routing } from "./lib/i18n";
import type { NextRequest } from "next/server";

const intlMiddleware = createIntlMiddleware(routing);

const isProtectedRoute = createRouteMatcher(["/:locale/dashboard(.*)"]);
const isAuthRoute = createRouteMatcher(["/:locale/login(.*)", "/:locale/register(.*)"]);

export const proxy = clerkMiddleware(async (auth, req: NextRequest) => {
	if (isProtectedRoute(req)) {
		await auth.protect();

		const dashboardLocale = req.cookies.get("DASHBOARD_LOCALE")?.value;
		const urlLocale = req.nextUrl.pathname.split("/")[1];
		if (dashboardLocale && (routing.locales as readonly string[]).includes(dashboardLocale) && dashboardLocale !== urlLocale) {
			const pathWithoutLocale = req.nextUrl.pathname.slice(`/${urlLocale}`.length);
			const redirectUrl = new URL(`/${dashboardLocale}${pathWithoutLocale}`, req.url);
			redirectUrl.search = req.nextUrl.search;
			return Response.redirect(redirectUrl);
		}
	}

	if (isAuthRoute(req)) {
		const { userId } = await auth();
		if (userId) {
			const locale = req.nextUrl.pathname.split("/")[1] || routing.defaultLocale;
			return Response.redirect(new URL(`/${locale}/dashboard`, req.url));
		}
	}

	return intlMiddleware(req);
});

export const config = {
	matcher: [
		"/((?!_next|[^?]*\\.(?:html?|css|js(?!on)|jpe?g|webp|png|gif|svg|ttf|woff2?|ico|csv|docx?|xlsx?|zip|webmanifest)).*)",
		"/(api|trpc)(.*)",
	],
};
