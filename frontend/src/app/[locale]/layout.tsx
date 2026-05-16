import type { Metadata } from "next";
import "../globals.css";
import QueryProvider from "@/components/providers/QueryProvider";
import GoogleAnalytics from "@/components/tracking/GoogleAnalytics";
import MicrosoftClarity from "@/components/tracking/MicrosoftClarity";
import { GA_MEASUREMENT_ID, CLARITY_PROJECT_ID } from "@/constants/app";
import { ClerkProvider } from "@clerk/nextjs";
import { NextIntlClientProvider } from "next-intl";
import { getMessages } from "next-intl/server";
import { notFound } from "next/navigation";
import { routing } from "@/i18n/routing";

export const metadata: Metadata = {
	title: "[Project Name]",
	description: "[Project description]",
	manifest: "/manifest.webmanifest",
	themeColor: "#6366f1",
};

const RootLayout = async ({ children, params }: TRootLayoutProps) => {
	const { locale } = await params;

	if (!routing.locales.includes(locale as (typeof routing.locales)[number])) {
		notFound();
	}

	const messages = await getMessages();

	return (
		<ClerkProvider>
			<html lang={locale} className="h-full antialiased">
				<head>
					<script
						dangerouslySetInnerHTML={{
							__html: `(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();`,
						}}
					/>
				</head>
				<body className="min-h-full flex flex-col">
					<NextIntlClientProvider messages={messages}>
						<QueryProvider>{children}</QueryProvider>
					</NextIntlClientProvider>
					{GA_MEASUREMENT_ID && <GoogleAnalytics measurementId={GA_MEASUREMENT_ID} />}
					{CLARITY_PROJECT_ID && <MicrosoftClarity projectId={CLARITY_PROJECT_ID} />}
				</body>
			</html>
		</ClerkProvider>
	);
};

type TRootLayoutProps = {
	children: React.ReactNode;
	params: Promise<{ locale: string }>;
};

export default RootLayout;
