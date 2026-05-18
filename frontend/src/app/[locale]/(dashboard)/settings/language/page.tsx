"use client";

import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import useLanguagePage from "./hooks/useLanguagePage";

const LanguagePage: React.FC = () => {
	const { isNavigating, labelText, localeOptions, saved, savedLabel, saveLabel, selectedLocale, title, handleLocaleChange, handleSave } = useLanguagePage();

	return (
		<div className="max-w-lg space-y-4">
			<h1 className="text-xl font-semibold">{title}</h1>
			<div className="space-y-2">
				<Label>{labelText}</Label>
				<Select value={selectedLocale} onValueChange={handleLocaleChange}>
					<SelectTrigger className="w-full">
						<SelectValue />
					</SelectTrigger>
					<SelectContent>
						{localeOptions.map(({ value, label }) => (
							<SelectItem key={value} value={value}>
								{label}
							</SelectItem>
						))}
					</SelectContent>
				</Select>
			</div>
			<div className="flex items-center gap-3">
				<Button onClick={handleSave} disabled={isNavigating} className="cursor-pointer">
					{saveLabel}
				</Button>
				{saved && <span className="text-sm text-[--state-success]">{savedLabel}</span>}
			</div>
		</div>
	);
};

export default LanguagePage;
