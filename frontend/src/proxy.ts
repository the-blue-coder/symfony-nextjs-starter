import { clerkMiddleware, createRouteMatcher } from "@clerk/nextjs/server";
import createIntlMiddleware from "next-intl/middleware";
import { routing } from "./i18n/routing";
import type { NextRequest } from "next/server";

const intlMiddleware = createIntlMiddleware(routing);

const isProtectedRoute = createRouteMatcher(["/:locale/dashboard(.*)"]);
const isAuthRoute = createRouteMatcher(["/:locale/login(.*)", "/:locale/register(.*)"]);

export const proxy = clerkMiddleware(async (auth, req: NextRequest) => {
	if (isProtectedRoute(req)) {
		await auth.protect();
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
