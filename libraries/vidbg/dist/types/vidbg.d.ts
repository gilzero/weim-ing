import { VidbgAttributes, VidbgOptions } from "./types/types";
declare class vidbg {
    el: HTMLElement;
    options: VidbgOptions;
    attributes: VidbgAttributes;
    containerEl: HTMLElement;
    overlayEl: HTMLElement;
    videoEl: HTMLVideoElement;
    constructor(selector: string, options?: VidbgOptions, attributes?: VidbgAttributes);
    private init;
    private createContainer;
    private createOverlay;
    private createPoster;
    private createVideo;
    private onPlayEvent;
    removeVideo: () => void;
    getVideo: () => HTMLVideoElement;
    getEl: () => HTMLElement;
    isVideoPlaying: () => boolean;
    playVideo: () => Promise<void>;
    pauseVideo: () => void;
    destroy: () => void;
    resize: () => void;
}
export { VidbgOptions, VidbgAttributes };
export default vidbg;
