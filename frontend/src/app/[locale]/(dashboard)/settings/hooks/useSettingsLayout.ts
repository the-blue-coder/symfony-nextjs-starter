"use client";

import { usePathname } from "@/lib/i18n";
import { useTranslations } from "next-intl";
import { useCallback } from "react";

const useSettingsLayout = () => {
	const pathname = usePathname();
	const t = useTranslations("nav");

	const navItems = [
		{ href: "/settings/language", label: t("language") },
	];

	const isNavActive = useCallback(
		(href: string) => {
			return pathname === href || pathname.startsWith(href + "/");
		},
		[pathname]
	);

	return { navItems, isNavActive };
};

export default useSettingsLayout;
