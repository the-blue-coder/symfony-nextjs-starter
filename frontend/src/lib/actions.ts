"use server";

import { cookies } from "next/headers";
import { routing } from "@/lib/i18n";

export const setDashboardLocale = async (locale: string): Promise<void> => {
	if (!(routing.locales as readonly string[]).includes(locale)) {
		return;
	}
	const jar = await cookies();
	jar.set("DASHBOARD_LOCALE", locale, { path: "/" });
};
