"use client";

import { useEffect, useRef, useState } from "react";

type TUseMercureReturn<T> = {
	data: T | null;
	isConnected: boolean;
};

const useMercure = <T = unknown>(topic: string): TUseMercureReturn<T> => {
	const [data, setData] = useState<T | null>(null);
	const [isConnected, setIsConnected] = useState(false);
	const eventSourceRef = useRef<EventSource | null>(null);

	useEffect(() => {
		const hubUrl = process.env.NEXT_PUBLIC_MERCURE_URL;
		if (!hubUrl) return;

		const url = new URL(hubUrl);
		url.searchParams.append("topic", topic);

		const eventSource = new EventSource(url.toString(), { withCredentials: true });
		eventSourceRef.current = eventSource;

		eventSource.onopen = () => setIsConnected(true);

		eventSource.onmessage = (event) => {
			try {
				setData(JSON.parse(event.data) as T);
			} catch {
				// ignore malformed messages
			}
		};

		eventSource.onerror = () => setIsConnected(false);

		return () => {
			eventSource.close();
			setIsConnected(false);
		};
	}, [topic]);

	return { data, isConnected };
};

export default useMercure;
