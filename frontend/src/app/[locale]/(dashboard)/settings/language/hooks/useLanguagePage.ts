"use client";

import { usePathname, useRouter } from "@/lib/i18n";
import { useLocale, useTranslations } from "next-intl";
import { useCallback, useState } from "react";

const useLanguagePage = () => {
	const currentLocale = useLocale();
	const router = useRouter();
	const pathname = usePathname();
	const t = useTranslations("settings.language");

	const labelText = t("label");
	const saveLabel = t("save");
	const savedLabel = t("saved");
	const title = t("title");

	const localeOptions = [
		{ value: "en", label: "English" },
		{ value: "fr", label: "Français" },
	];

	const [isNavigating, setIsNavigating] = useState(false);
	const [saved, setSaved] = useState(false);
	const [selectedLocale, setSelectedLocale] = useState(currentLocale);

	const handleLocaleChange = useCallback((value: string) => {
		setSaved(false);
		setSelectedLocale(value);
	}, []);

	const handleSave = useCallback(() => {
		setSaved(true);
		if (selectedLocale !== currentLocale) {
			setIsNavigating(true);
			router.replace(pathname, { locale: selectedLocale });
		}
	}, [currentLocale, pathname, router, selectedLocale]);

	return { isNavigating, labelText, localeOptions, saved, savedLabel, saveLabel, selectedLocale, title, handleLocaleChange, handleSave };
};

export default useLanguagePage;
