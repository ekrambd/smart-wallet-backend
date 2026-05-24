const { ethers } = require("ethers");

// Command Line Input
const privateKey = process.argv[2];
const tokenAddress = process.argv[3];
const amount = process.argv[4];
const decimals = process.argv[5];

// RPC
const RPC_URL = "https://bsc-dataseed.binance.org/";

// PancakeSwap Router
const ROUTER_ADDRESS =
  "0x10ED43C718714eb63d5aA57B78B54704E256024E";

// WBNB
const WBNB =
  "0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c";

const routerAbi = [
  "function getAmountsOut(uint amountIn, address[] memory path) public view returns (uint[] memory amounts)",

  "function swapExactTokensForETHSupportingFeeOnTransferTokens(" +
    "uint amountIn," +
    "uint amountOutMin," +
    "address[] calldata path," +
    "address to," +
    "uint deadline" +
    ") external"
];

const tokenAbi = [
  "function approve(address spender, uint amount) external returns (bool)"
];

async function main() {
  try {

    // Provider
    const provider =
      new ethers.providers.JsonRpcProvider(
        RPC_URL
      );

    // Wallet
    const wallet = new ethers.Wallet(
      privateKey,
      provider
    );

    // Router
    const router = new ethers.Contract(
      ROUTER_ADDRESS,
      routerAbi,
      wallet
    );

    // Token Contract
    const token = new ethers.Contract(
      tokenAddress,
      tokenAbi,
      wallet
    );

    // Token Amount
    const amountIn =
      ethers.utils.parseUnits(
        amount,
        decimals
      );

    // Approve
    const approveTx = await token.approve(
      ROUTER_ADDRESS,
      amountIn
    );

    await approveTx.wait();

    // Path
    const path = [tokenAddress, WBNB];

    // Expected Output
    const amounts =
      await router.getAmountsOut(
        amountIn,
        path
      );

    const expectedOut = amounts[1];

    /*
      MAX SLIPPAGE
      minimum output = 1 wei
    */
    const amountOutMin = 1;

    // Deadline
    const deadline =
      Math.floor(Date.now() / 1000) + 60 * 20;

    // Swap
    const tx =
      await router.swapExactTokensForETHSupportingFeeOnTransferTokens(
        amountIn,
        amountOutMin,
        path,
        wallet.address,
        deadline,
        {
          gasLimit: 600000
        }
      );

    await tx.wait();

    console.log(tx.hash);

  } catch (err) {

    //console.log("failed to swap");
    //console.log(err.message);

    console.log("failed to swap");

  }
}

main();