import { defineRouting } from "next-intl/routing";
import { createNavigation } from "next-intl/navigation";
import { getRequestConfig } from "next-intl/server";

export const routing = defineRouting({
	locales: ["en", "fr"],
	defaultLocale: "en",
});

export const { Link, redirect, usePathname, useRouter } = createNavigation(routing);

export default getRequestConfig(async ({ requestLocale }) => {
	const requested = await requestLocale;
	const locale = routing.locales.includes(requested as (typeof routing.locales)[number])
		? requested!
		: routing.defaultLocale;

	return {
		locale,
		messages: (await import(`../i18n/${locale}.json`)).default,
	};
});
