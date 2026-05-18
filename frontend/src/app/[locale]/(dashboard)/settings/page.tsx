import { redirect } from "next/navigation";

const SettingsPage = async ({ params }: TSettingsPageProps) => {
	const { locale } = await params;
	redirect(`/${locale}/settings/language`);
};

type TSettingsPageProps = {
	params: Promise<{ locale: string }>;
};

export default SettingsPage;
