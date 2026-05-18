import { auth } from "@clerk/nextjs/server";
import { Link, redirect } from "@/lib/i18n";
import { Button } from "@/components/ui/button";

const RootPage: React.FC = async () => {
	const { userId } = await auth();

	if (userId) {
		redirect("/dashboard");
	}

	return (
		<div className="flex flex-col min-h-screen">
			{/* Nav */}
			<header className="border-b">
				<div className="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
					<div className="flex items-center gap-2">
						<img src="/icon.svg" alt="[Project Name]" className="h-7 w-7 rounded-md" />
						<span className="font-semibold">[Project Name]</span>
					</div>
					<div className="flex items-center gap-3">
						<Link href="/login">
							<Button variant="ghost" size="sm" className="cursor-pointer">
								Sign in
							</Button>
						</Link>
						<Link href="/register">
							<Button size="sm" className="cursor-pointer">
								Get started
							</Button>
						</Link>
					</div>
				</div>
			</header>

			{/* Hero */}
			<section className="flex-1 flex flex-col items-center justify-center text-center px-4 py-24 gap-6">
				<h1 className="text-4xl sm:text-5xl font-bold tracking-tight max-w-2xl">[Your tagline goes here]</h1>
				<p className="text-muted-foreground text-lg max-w-xl">[Short description of the product — one or two sentences explaining what it does and who it&apos;s for.]</p>
				<div className="flex gap-3">
					<Link href="/register">
						<Button size="lg" className="cursor-pointer">
							Get started free
						</Button>
					</Link>
					<Link href="/login">
						<Button size="lg" variant="outline" className="cursor-pointer">
							Sign in
						</Button>
					</Link>
				</div>
			</section>

			{/* Features */}
			<section className="border-t py-20 px-4">
				<div className="max-w-5xl mx-auto">
					<h2 className="text-2xl font-semibold text-center mb-12">Everything you need</h2>
					<div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
						{features.map((feature) => (
							<div key={feature.title} className="rounded-lg border bg-card p-6 space-y-2">
								<h3 className="font-medium">{feature.title}</h3>
								<p className="text-sm text-muted-foreground">{feature.description}</p>
							</div>
						))}
					</div>
				</div>
			</section>

			{/* Footer */}
			<footer className="border-t py-6 text-center text-sm text-muted-foreground">© {new Date().getFullYear()} [Project Name]. All rights reserved.</footer>
		</div>
	);
};

const features = [
	{ title: "Feature one", description: "Short description of what this feature does for the user." },
	{ title: "Feature two", description: "Short description of what this feature does for the user." },
	{ title: "Feature three", description: "Short description of what this feature does for the user." },
];

export default RootPage;
