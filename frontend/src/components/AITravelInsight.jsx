import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Bot, ChevronDown, Sparkles, AlertCircle } from 'lucide-react';

// LEVEL 3+: AI Travel Analysis (Bundled)
export default function AITravelInsight({
    source,
    destination,
    mode,
    cost_range,
    duration,
    reason_tag
}) {
    const [insight, setInsight] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [expandedSection, setExpandedSection] = useState(null);

    const toggleSection = (section) => {
        if (expandedSection === section) {
            setExpandedSection(null);
        } else {
            setExpandedSection(section);
        }
    };

    useEffect(() => {
        setInsight(null);
        setError(false);
        setExpandedSection(null);
        setLoading(true);

        if (!source || !destination || !mode) return;

        const fetchInsight = async () => {
            try {
                const payload = {
                    source,
                    destination,
                    mode,
                    cost_range: cost_range || 'unknown',
                    duration: typeof duration === 'number' ? `${msgFormatMinutes(duration)}` : duration,
                    reason_tag: reason_tag || 'None'
                };

                const response = await axios.post('http://localhost:8000/api/ai-insight.php', payload);

                if (response.data && response.data.insight) {
                    setInsight(response.data.insight);
                } else {
                    setError(true);
                }
            } catch (err) {
                console.error("AI Insight fetch failed silently:", err);
                setError(true);
            } finally {
                setLoading(false);
            }
        };

        const timer = setTimeout(() => {
            fetchInsight();
        }, 500);

        return () => clearTimeout(timer);

    }, [source, destination, mode, cost_range, duration, reason_tag]);

    const msgFormatMinutes = (mins) => {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return `${h}h ${m}m`;
    };

    if (error) {
        return (
            <div className="mt-4 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 bg-slate-50/60 dark:bg-slate-800/40 text-center">
                <div className="flex items-center justify-center gap-2">
                    <AlertCircle size={14} className="text-slate-400" />
                    <p className="text-xs italic" style={{ color: 'var(--riq-text-faint)' }}>AI Analysis unavailable at the moment.</p>
                </div>
            </div>
        );
    }

    if (loading) {
        return (
            <div className="mt-4 p-5 rounded-2xl border border-violet-200/40 dark:border-violet-700/30 bg-gradient-to-br from-violet-50/50 to-indigo-50/30 dark:from-violet-900/10 dark:to-indigo-900/10 riq-pulse">
                <div className="h-4 bg-violet-200/60 dark:bg-violet-800/30 rounded-full w-1/4 mb-3" />
                <div className="h-4 bg-violet-200/60 dark:bg-violet-800/30 rounded-full w-3/4 mb-2" />
                <div className="h-4 bg-violet-200/60 dark:bg-violet-800/30 rounded-full w-1/2" />
            </div>
        );
    }

    if (!insight) return null;

    const isStructured = typeof insight === 'object';
    const mainReason = isStructured ? insight.recommendationReason : insight;

    const sections = [
        {
            key: 'whynot',
            label: 'Why not other options?',
            show: isStructured && insight.whyNotOthers && insight.whyNotOthers.length > 0,
            content: isStructured && insight.whyNotOthers ? (
                <ul className="space-y-2 mt-2">
                    {insight.whyNotOthers.map((reason, idx) => (
                        <li key={idx} className="flex items-start gap-2 text-sm" style={{ color: 'var(--riq-text-muted)' }}>
                            <span className="mt-1 w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500 flex-shrink-0" />
                            {reason}
                        </li>
                    ))}
                </ul>
            ) : null,
        },
        {
            key: 'tips',
            label: 'Route-Specific Tips',
            show: isStructured && insight.travelTips && insight.travelTips.length > 0,
            content: isStructured && insight.travelTips ? (
                <ul className="space-y-2 mt-2">
                    {insight.travelTips.map((tip, idx) => (
                        <li key={idx} className="flex items-start gap-2 text-sm" style={{ color: 'var(--riq-text-muted)' }}>
                            <span className="mt-1 w-1.5 h-1.5 rounded-full bg-violet-400 dark:bg-violet-500 flex-shrink-0" />
                            {tip}
                        </li>
                    ))}
                </ul>
            ) : null,
        },
        {
            key: 'summary',
            label: 'Trip Summary',
            show: isStructured && insight.summary,
            content: isStructured && insight.summary ? (
                <p className="text-sm mt-2 leading-relaxed" style={{ color: 'var(--riq-text-muted)' }}>
                    {insight.summary}
                </p>
            ) : null,
        }
    ];

    return (
        <div className="mt-4 overflow-hidden rounded-2xl border border-violet-200/40 dark:border-violet-700/30 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-sm">
            {/* Header & Main Reason */}
            <div className="p-5 bg-gradient-to-br from-violet-50/60 to-indigo-50/30 dark:from-violet-900/15 dark:to-indigo-900/10">
                <div className="flex items-center gap-2 mb-2.5">
                    <div className="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-500/15 to-indigo-500/10 flex items-center justify-center border border-violet-300/30 dark:border-violet-600/30">
                        <Bot size={14} className="text-violet-600 dark:text-violet-400" />
                    </div>
                    <h4 className="text-xs font-bold uppercase tracking-wider text-violet-800 dark:text-violet-300">
                        AI Travel Analysis
                    </h4>
                    <Sparkles size={12} className="text-violet-400 dark:text-violet-500" />
                </div>
                <p className="text-sm leading-relaxed font-medium" style={{ color: 'var(--riq-text)' }}>
                    {mainReason}
                </p>
            </div>

            {/* Accordion Sections */}
            {sections.filter(s => s.show).length > 0 && (
                <div className="border-t border-slate-100 dark:border-slate-700/50 divide-y divide-slate-100 dark:divide-slate-700/50">
                    {sections.filter(s => s.show).map((section) => (
                        <div key={section.key}>
                            <button
                                onClick={() => toggleSection(section.key)}
                                className="w-full flex justify-between items-center p-4 text-left hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                type="button"
                            >
                                <span className="text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--riq-text-faint)' }}>
                                    {section.label}
                                </span>
                                <ChevronDown
                                    size={14}
                                    className={`transition-transform duration-200 ${expandedSection === section.key ? 'rotate-180' : ''}`}
                                    style={{ color: 'var(--riq-text-faint)' }}
                                />
                            </button>
                            {expandedSection === section.key && (
                                <div className="px-4 pb-4 pt-0 riq-fade-up" style={{ animationDuration: '200ms' }}>
                                    {section.content}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
